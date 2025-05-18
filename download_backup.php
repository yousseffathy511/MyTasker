<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

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
    die('Access denied. Admin privileges required.');
    
    // Log unauthorized access attempt
    if (function_exists('logAuditEvent')) {
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: download_backup.php');
    }
}

// Validate CSRF token
if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
    die('Invalid security token');
}

// Sanitize and validate the filename
$filename = isset($_GET['file']) ? basename($_GET['file']) : '';

// Make sure file has .sql extension
if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
    die('Invalid backup file specified');
}

// Set the full file path
$filepath = __DIR__ . '/backups/' . $filename;

// Check if file exists and is readable
if (!file_exists($filepath) || !is_readable($filepath)) {
    die('Backup file not found or not readable');
}

// Log the download if audit logging is available
if (function_exists('logAuditEvent')) {
    logAuditEvent('BACKUP_DOWNLOAD', 'Downloaded database backup: ' . $filename);
}

// Set headers for file download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// Clear any previous output
ob_clean();
flush();

// Read the file and output it to the browser
readfile($filepath);
exit;
?>