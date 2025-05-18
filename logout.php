<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Perform logout
logoutUser();

// Set message and redirect
setFlashMessage('You have been logged out successfully', 'info');
header('Location: login.php');
exit; 