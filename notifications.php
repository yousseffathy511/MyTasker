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

// Get user ID
$userId = $_SESSION['user_id'];
$database = new Database();
$conn = $database->getConnection();

// Process marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    // Verify CSRF token
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $notificationId = filter_var($_POST['notification_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if ($notificationId > 0) {
            try {
                // Check if already read
                $checkStmt = $conn->prepare("
                    SELECT id FROM notification_reads 
                    WHERE notification_id = ? AND user_id = ?
                ");
                $checkStmt->execute([$notificationId, $userId]);
                
                if ($checkStmt->rowCount() === 0) {
                    // Mark as read
                    $stmt = $conn->prepare("
                        INSERT INTO notification_reads (notification_id, user_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$notificationId, $userId]);
                    
                    // Log action
                    if (function_exists('logAuditEvent')) {
                        logAuditEvent('READ_NOTIFICATION', 'User marked notification as read', $userId, 'notification', $notificationId);
                    }
                }
            } catch (PDOException $e) {
                // Log error
                error_log('Error marking notification as read: ' . $e->getMessage());
            }
        }
    }
    
    // Redirect to refresh page
    header('Location: notifications.php');
    exit;
}

// Get all notifications
$notifications = [];
try {
    // Check if notifications table exists
    $tables = $conn->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
    
    if (!empty($tables)) {
        // Get all notifications with read status for this user
        $stmt = $conn->prepare("
            SELECT 
                n.id, 
                n.title, 
                n.message, 
                n.created_at,
                u.name as admin_name,
                CASE WHEN nr.id IS NULL THEN 0 ELSE 1 END as is_read
            FROM notifications n
            JOIN users u ON n.created_by = u.id
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Log error
    error_log('Error getting notifications: ' . $e->getMessage());
}

// HTML head and header
$pageTitle = 'Notifications';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="bi bi-bell"></i> <?php echo $pageTitle; ?></h1>
            <p class="text-muted">View important announcements from administrators</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Tasks
            </a>
        </div>
    </div>

    <!-- Custom style for darkening the hover effect -->
    <style>
        .list-group-item-action:hover {
            background-color: rgba(0, 0, 0, 0.08) !important;
        }
    </style>

    <div class="card shadow">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0"><i class="bi bi-megaphone"></i> Announcements</h2>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No announcements available.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-warning'; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-danger me-2">New</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </h5>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($notification['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    From: <?php echo htmlspecialchars($notification['admin_name']); ?>
                                </small>
                                
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" action="notifications.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <input type="hidden" name="mark_read" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-check-circle"></i> Mark as Read
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-check-circle"></i> Read
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 