<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// 3) General functions (including restore_database())
require_once __DIR__ . '/includes/functions.php';

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
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: restore.php');
    }
    
    header('Location: index.php');
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Invalid request method', 'danger');
    header('Location: backup.php');
    exit;
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Invalid security token, please try again', 'danger');
    header('Location: backup.php');
    exit;
}

// Read and sanitize filename
$filename = isset($_POST['filename']) ? basename($_POST['filename']) : '';

// Validate filename
if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
    setFlashMessage('Invalid backup file specified', 'danger');
    header('Location: backup.php');
    exit;
}

// Call restore function
$result = restore_database($filename);

// Handle result
if ($result['success']) {
    setFlashMessage('Database successfully restored from: ' . $filename, 'success');
} else {
    setFlashMessage('Error restoring database: ' . $result['message'], 'danger');
}

// Redirect back to backup page
header('Location: backup.php');
exit; 