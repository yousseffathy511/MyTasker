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
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: delete_notification.php');
    }
    
    header('Location: index.php');
    exit;
}

// Validate the request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['notification_id'], $_POST['csrf_token'])) {
    setFlashMessage('Invalid request.', 'danger');
    header('Location: admin.php');
    exit;
}

// CSRF validation
if (!validateCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('Invalid security token. Please try again.', 'danger');
    header('Location: admin.php');
    exit;
}

$notificationId = intval($_POST['notification_id']);

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

// Redirect back to referring page or admin dashboard
$redirect = $_POST['redirect'] ?? 'admin.php';
header("Location: $redirect");
exit; 