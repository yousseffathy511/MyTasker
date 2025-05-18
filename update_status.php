<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// 3) Task-related functions
require_once __DIR__ . '/includes/task.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Only allow POST method for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Method not allowed
    setFlashMessage('Invalid request method', 'danger');
    header('Location: index.php');
    exit;
}

// Verify CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Invalid form submission, please try again', 'danger');
    header('Location: index.php');
    exit;
}

// Get and validate task ID
$taskId = filter_var($_POST['task_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$taskId) {
    // Invalid task ID
    setFlashMessage('Invalid task ID', 'danger');
    header('Location: index.php');
    exit;
}

// Toggle the task status
$result = toggleTaskStatus($taskId, $userId);

// Set appropriate flash message based on result
if ($result['success']) {
    // Get the task to determine its new status
    $task = getTask($taskId, $userId);
    $statusMessage = $task && $task['is_done'] ? 'Task marked as completed' : 'Task marked as pending';
    
    setFlashMessage($statusMessage, 'success');
} else {
    setFlashMessage($result['message'], 'danger');
}

// Redirect back to dashboard
header('Location: index.php');
exit; 