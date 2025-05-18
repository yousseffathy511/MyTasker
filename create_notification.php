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
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: create_notification.php');
    }
    
    header('Location: index.php');
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Invalid request method', 'danger');
    header('Location: admin.php');
    exit;
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Invalid security token, please try again', 'danger');
    header('Location: admin.php');
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Get and validate form data
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate required fields
$errors = [];

if (empty($title)) {
    $errors[] = 'Title is required';
} elseif (strlen($title) > 100) {
    $errors[] = 'Title must be 100 characters or less';
}

if (empty($message)) {
    $errors[] = 'Message is required';
} elseif (strlen($message) > 1000) {
    $errors[] = 'Message must be 1000 characters or less';
}

// If validation errors, redirect back with error message
if (!empty($errors)) {
    setFlashMessage('Error creating announcement: ' . implode(', ', $errors), 'danger');
    header('Location: admin.php');
    exit;
}

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Create notifications table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notification_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            notification_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY (notification_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Insert the notification
    $stmt = $conn->prepare("
        INSERT INTO notifications (title, message, created_by) 
        VALUES (?, ?, ?)
    ");
    
    $result = $stmt->execute([$title, $message, $userId]);
    
    if ($result) {
        // Log the action
        if (function_exists('logAuditEvent')) {
            logAuditEvent('CREATE_NOTIFICATION', 'Admin created a new announcement');
        }
        
        setFlashMessage('Announcement created successfully', 'success');
    } else {
        setFlashMessage('Failed to create announcement', 'danger');
    }
    
} catch (PDOException $e) {
    // Log the error
    error_log('Error creating notification: ' . $e->getMessage());
    
    setFlashMessage('Database error occurred', 'danger');
}

// Redirect back to admin page
header('Location: admin.php');
exit; 