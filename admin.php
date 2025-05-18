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
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: admin.php');
    }
    
    header('Location: index.php');
    exit;
}

// Handle notification deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['notification_id'], $_POST['csrf_token'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid token. Please try again.', 'danger');
        header('Location: admin.php');
        exit;
    }
    
    $notificationId = intval($_POST['notification_id']);
    $action = $_POST['action'];
    
    if ($action === 'delete_notification') {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // First delete related read records
            $stmt = $conn->prepare("DELETE FROM notification_reads WHERE notification_id = ?");
            $stmt->execute([$notificationId]);
            
            // Then delete the notification
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            
            setFlashMessage('Announcement deleted successfully.', 'success');
            
            // Log the deletion
            if (function_exists('logAuditEvent')) {
                logAuditEvent('NOTIFICATION_DELETED', "Deleted announcement #{$notificationId}");
            }
        } catch (PDOException $e) {
            setFlashMessage('Error: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: admin.php');
        exit;
    }
}

// Define backup directory
$backupDir = __DIR__ . '/backups';

// Create backup directory if it doesn't exist
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Count backup files
$backupCount = 0;
$backupFiles = [];
if (file_exists($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backupCount++;
            $filePath = $backupDir . '/' . $file;
            $backupFiles[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath)
            ];
        }
    }
    
    // Sort by modified time (newest first)
    usort($backupFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    // Get only most recent 5
    $backupFiles = array_slice($backupFiles, 0, 5);
}

// Count total users
$userCount = 0;
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $userCount = $result['count'] ?? 0;
} catch (PDOException $e) {
    // Log the error
    error_log('Error counting users: ' . $e->getMessage());
}

// Get recent notifications
$notifications = [];
try {
    $stmt = $conn->prepare("
        SELECT n.id, n.title, n.message, n.created_at, COUNT(nr.id) as read_count
        FROM notifications n
        LEFT JOIN notification_reads nr ON n.id = nr.notification_id
        GROUP BY n.id
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet, handle silently
}

// HTML head and header
$pageTitle = 'Admin Dashboard';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-speedometer2"></i> <?php echo $pageTitle; ?></h1>
                    <p class="text-muted">Manage your application, users, and data</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-float" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
                        <i class="bi bi-plus-circle"></i> New Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Dashboard Overview -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-value"><?php echo $userCount; ?></div>
                            <div class="stat-card-label">Total Users</div>
                        </div>
                        <div>
                            <i class="bi bi-people-fill" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="users.php" class="btn btn-light btn-sm">
                            Manage Users <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-value"><?php echo $backupCount; ?></div>
                            <div class="stat-card-label">Backup Files</div>
                        </div>
                        <div>
                            <i class="bi bi-database" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="backup.php" class="btn btn-light btn-sm">
                            Manage Backups <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-value"><?php echo count($notifications); ?></div>
                            <div class="stat-card-label">Announcements</div>
                        </div>
                        <div>
                            <i class="bi bi-megaphone" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
                            Create Announcement <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Backups -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-primary text-white">
                            <i class="bi bi-database"></i>
                        </div>
                        <h2 class="h5 mb-0">Recent Backups</h2>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($backupFiles)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No backup files found.</p>
                            <a href="backup.php" class="btn btn-primary">Create Backup</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($backupFiles as $file): ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-file-earmark-code text-primary me-2"></i>
                                        <strong><?php echo htmlspecialchars($file['name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> 
                                            <?php echo date('F d, Y - H:i', $file['modified']); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="download_backup.php?file=<?php echo urlencode($file['name']); ?>&csrf_token=<?php echo generateCsrfToken(); ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="backup.php" class="btn btn-outline-secondary">
                                View All Backups <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Announcements -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-info text-white">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h2 class="h5 mb-0">Recent Announcements</h2>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No announcements have been created.</p>
                            <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
                                Create First Announcement
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <div>
                                            <small class="text-muted me-2">
                                                <i class="bi bi-calendar"></i>
                                                <?php echo date('M d, Y', strtotime($notification['created_at'])); ?>
                                            </small>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <input type="hidden" name="action" value="delete_notification">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this announcement?');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-success">
                                        <i class="bi bi-eye"></i> Read by <?php echo $notification['read_count']; ?> users
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Notification Modal -->
<div class="modal fade" id="addNotificationModal" tabindex="-1" aria-labelledby="addNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="create_notification.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addNotificationModalLabel">
                        <i class="bi bi-megaphone"></i> Create New Announcement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Title Field -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Announcement Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               required maxlength="100" placeholder="Enter a descriptive title">
                    </div>
                    
                    <!-- Message Field -->
                    <div class="mb-3">
                        <label for="message" class="form-label">Announcement Message</label>
                        <textarea class="form-control" id="message" name="message" 
                                  rows="4" required maxlength="1000" 
                                  placeholder="Enter the announcement content that users will see"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Send Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 