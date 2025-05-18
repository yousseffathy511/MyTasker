<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// 3) General functions (if needed)
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

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Invalid request method', 'danger');
    header('Location: profile.php');
    exit;
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Invalid security token, please try again', 'danger');
    header('Location: profile.php');
    exit;
}

// Get current user ID
$userId = $_SESSION['user_id'];

// Function to delete a user account
function deleteUser($userId) {
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Start transaction
        $conn->beginTransaction();
        
        // Log the account deletion action before deleting the user
        if (function_exists('logAuditEvent')) {
            logAuditEvent('ACCOUNT_DELETE', 'User deleted their account');
        }
        
        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$userId]);
        
        if (!$result) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'Failed to delete account'];
        }
        
        // Commit the transaction
        $conn->commit();
        return ['success' => true];
        
    } catch (PDOException $e) {
        // Roll back the transaction
        if (isset($conn)) {
            $conn->rollBack();
        }
        
        // Log the error
        error_log('Account deletion error: ' . $e->getMessage());
        
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

// Delete the account
$result = deleteUser($userId);

// Handle the result
if ($result['success']) {
    // Destroy the session
    session_start();
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Redirect to login page with success message
    setFlashMessage('Your account has been permanently deleted.', 'success');
    header('Location: login.php');
    exit;
} else {
    // Redirect back to profile page with error message
    setFlashMessage('Failed to delete account: ' . ($result['message'] ?? 'Unknown error'), 'danger');
    header('Location: profile.php');
    exit;
} 