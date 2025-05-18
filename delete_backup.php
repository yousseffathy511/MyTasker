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

// Define backup directory and full file path
$backupDir = __DIR__ . '/backups';
$filePath = $backupDir . '/' . $filename;

// Check if file exists
if (!file_exists($filePath)) {
    setFlashMessage('Backup file not found', 'danger');
    header('Location: backup.php');
    exit;
}

// Delete the file
if (unlink($filePath)) {
    setFlashMessage('Backup file "' . $filename . '" has been deleted successfully', 'success');
} else {
    setFlashMessage('Error deleting backup file', 'danger');
}

// Redirect back to backup page
header('Location: backup.php');
exit; 