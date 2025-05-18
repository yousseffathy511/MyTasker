<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// 3) General functions
require_once __DIR__ . '/includes/functions.php';

// 4) Audit logging
if (file_exists(__DIR__ . '/includes/audit.php')) {
    require_once __DIR__ . '/includes/audit.php';
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Restrict access to admins only
if (!isAdmin()) {
    setFlashMessage('Access denied. Admin privileges required.', 'danger');
    
    // Log unauthorized access attempt
    if (function_exists('logAuditEvent')) {
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: users.php');
    }
    
    header('Location: index.php');
    exit;
}

// Handle user actions (role change, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'], $_POST['csrf_token'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid token. Please try again.', 'danger');
        header('Location: users.php');
        exit;
    }
    
    $userId = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Don't allow admins to modify their own account
        $currentUserId = $_SESSION['user_id'] ?? 0;
        if ($userId === $currentUserId) {
            setFlashMessage('You cannot modify your own account.', 'warning');
            header('Location: users.php');
            exit;
        }
        
        switch ($action) {
            case 'toggle_admin':
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->execute([$newRole, $userId]);
                    
                    $message = ($newRole === 'admin') ? 'User promoted to admin.' : 'Admin demoted to regular user.';
                    setFlashMessage($message, 'success');
                    
                    // Log the role change
                    if (function_exists('logAuditEvent')) {
                        logAuditEvent('USER_ROLE_CHANGE', "Changed user #{$userId} role to {$newRole}");
                    }
                }
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                
                setFlashMessage('User account deleted successfully.', 'success');
                
                // Log the deletion
                if (function_exists('logAuditEvent')) {
                    logAuditEvent('USER_DELETED', "Deleted user #{$userId}");
                }
                break;
        }
    } catch (PDOException $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: users.php');
    exit;
}

// Get all users with their active status and role
$users = [];
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            id, 
            name, 
            email, 
            role,
            created_at,
            last_activity,
            CASE
                WHEN last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Active'
                WHEN last_activity > DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 'Inactive'
                ELSE 'Dormant'
            END as status
        FROM users
        ORDER BY 
            role DESC,
            last_activity DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error
    error_log('Error fetching users: ' . $e->getMessage());
}

// HTML head and header
$pageTitle = 'User Management';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="bi bi-people"></i> <?php echo $pageTitle; ?></h1>
            <p class="text-muted">Manage user accounts in your system</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="admin.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php displayFlashMessages(); ?>

    <div class="card shadow">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0"><i class="bi bi-person-lines-fill"></i> User Accounts</h2>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No user accounts found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td style="color: white;"><?php echo $user['id']; ?></td>
                                    <td style="color: white;"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td style="color: white;"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td style="color: white;">
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: white;">
                                        <?php 
                                        switch ($user['status']) {
                                            case 'Active':
                                                echo '<span class="badge bg-success">Active</span>';
                                                break;
                                            case 'Inactive':
                                                echo '<span class="badge bg-warning text-dark">Inactive</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-danger">Dormant</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="color: white;"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td style="color: white;">
                                        <?php 
                                        if (!empty($user['last_activity'])) {
                                            echo date('Y-m-d', strtotime($user['last_activity']));
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once 'includes/footer.php'; ?> 