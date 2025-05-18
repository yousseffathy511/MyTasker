<?php
/**
 * Authentication Module
 * 
 * This file handles user registration and login functionality
 * using the Database class for database operations.
 * 
 * @package MyTasker
 */

// Include Database class
require_once __DIR__ . '/../config/Database.php';

// Include Audit logging
require_once __DIR__ . '/audit.php';

/**
 * Register a new user
 * 
 * Validates input data, checks for existing email, hashes password,
 * and inserts a new user record into the database.
 * 
 * @param string $name The user's full name
 * @param string $email The user's email address
 * @param string $password The user's password (plaintext)
 * @param bool $dataRetentionApproved Whether the user has approved data retention policy
 * @return array Contains success status and user ID or error message
 */
function registerUser(string $name, string $email, string $password, bool $dataRetentionApproved = false): array
{
    // Validate inputs
    $errors = [];
    
    // Validate name
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must be 100 characters or less';
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } elseif (strlen($email) > 150) {
        $errors[] = 'Email must be 150 characters or less';
    }
    
    // Validate password strength
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    // Return errors if validation fails
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email address already registered'];
        }
        
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user with PDPA data retention approval
        $insertStmt = $conn->prepare("
            INSERT INTO users (
                name, 
                email, 
                password_hash, 
                data_retention_approved, 
                data_retention_date
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $currentDate = date('Y-m-d H:i:s');
        $success = $insertStmt->execute([
            $name, 
            $email, 
            $passwordHash, 
            $dataRetentionApproved ? 1 : 0, 
            $dataRetentionApproved ? $currentDate : null
        ]);
        
        if ($success) {
            $userId = $conn->lastInsertId();
            
            // Log the registration action
            logUserAction(
                $userId,
                'register',
                'user',
                $userId,
                'User registration'
            );
            
            return ['success' => true, 'userId' => $userId];
        } else {
            return ['success' => false, 'message' => 'Failed to create user account'];
        }
        
    } catch (PDOException $e) {
        // Log the error (in a production environment)
        error_log('Registration error: ' . $e->getMessage());
        
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Authenticate a user with brute force protection
 * 
 * Validates credentials, creates a session, and regenerates session ID
 * to prevent session fixation attacks. Also implements brute force protection.
 * 
 * @param string $email The user's email address
 * @param string $password The user's password (plaintext)
 * @return array Contains success status and error message if applicable
 */
function loginUser(string $email, string $password): array
{
    // Validate inputs
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }
    
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get user by email with login attempts data
        $stmt = $conn->prepare("
            SELECT id, name, email, password_hash, login_attempts, 
                   last_login_attempt, account_locked, role
            FROM users 
            WHERE email = ?
        ");
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Check if user exists
        if (!$user) {
            // User not found, but don't reveal this fact
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Check if account is locked
        if ($user['account_locked']) {
            return ['success' => false, 'message' => 'Account is locked due to multiple failed login attempts. Contact an administrator.'];
        }
        
        // Check for too many login attempts
        $maxAttempts = 5;
        $lockoutTime = 15 * 60; // 15 minutes in seconds
        
        if ($user['login_attempts'] >= $maxAttempts) {
            $lastAttemptTime = strtotime($user['last_login_attempt'] ?? '0000-00-00 00:00:00');
            $currentTime = time();
            
            if (($currentTime - $lastAttemptTime) < $lockoutTime) {
                // Account is temporarily locked
                $minutesLeft = ceil(($lockoutTime - ($currentTime - $lastAttemptTime)) / 60);
                return [
                    'success' => false, 
                    'message' => "Too many failed login attempts. Please try again in {$minutesLeft} minute(s)."
                ];
            } else {
                // Lockout period expired, reset attempts
                $resetStmt = $conn->prepare("
                    UPDATE users 
                    SET login_attempts = 0 
                    WHERE id = ?
                ");
                $resetStmt->execute([$user['id']]);
                $user['login_attempts'] = 0;
            }
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Reset login attempts and update last login time
            $updateStmt = $conn->prepare("
                UPDATE users 
                SET login_attempts = 0,
                    last_login_attempt = NULL,
                    last_login = NOW(),
                    last_activity = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$user['id']]);
            
            // Log successful login
            logUserAction(
                $user['id'],
                'login',
                'user',
                $user['id'],
                'User login successful'
            );
            
            return ['success' => true];
        } else {
            // Increment login attempts
            $attemptStmt = $conn->prepare("
                UPDATE users 
                SET login_attempts = login_attempts + 1,
                    last_login_attempt = NOW()
                WHERE id = ?
            ");
            $attemptStmt->execute([$user['id']]);
            
            // Log failed login attempt
            logUserAction(
                $user['id'],
                'login_failed',
                'user',
                $user['id'],
                'Failed login attempt (' . ($user['login_attempts'] + 1) . ')'
            );
            
            // Check if account should be locked
            if ($user['login_attempts'] + 1 >= $maxAttempts) {
                return [
                    'success' => false, 
                    'message' => 'Too many failed login attempts. Your account has been temporarily locked. Please try again later.'
                ];
            }
            
            // Authentication failed
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
    } catch (PDOException $e) {
        // Log the error (in a production environment)
        error_log('Login error: ' . $e->getMessage());
        
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Generate a CSRF token
 * 
 * Creates a random token and stores it in the session to prevent CSRF attacks.
 * 
 * @return string The generated CSRF token
 */
function generateCsrfToken(): string
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate a new token if one doesn't exist
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 * 
 * Compares the provided token with the one stored in the session.
 * 
 * @param string $token The token to validate
 * @return bool True if token is valid, false otherwise
 */
function validateCsrfToken(string $token): bool
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if token matches
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    
    return true;
}

/**
 * Set a flash message to be displayed on the next page load
 * 
 * @param string $message The message to display
 * @param string $type The message type (success, danger, info, warning)
 * @return void
 */
function setFlashMessage(string $message, string $type = 'info'): void
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Display flash messages and clear them from the session
 * 
 * @return void
 */
function displayFlashMessages(): void
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if flash message exists
    if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        // Display the message
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        // Clear the flash message
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Check if user session is active and valid
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn(): bool
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
        // Check for session timeout (30 minutes of inactivity)
        $timeout = 30 * 60; // 30 minutes in seconds
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            // Session has expired
            logoutUser();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Update last activity in database if more than 5 minutes have passed
        if (isset($_SESSION['last_db_activity']) && (time() - $_SESSION['last_db_activity'] > 300)) {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $_SESSION['last_db_activity'] = time();
            } catch (PDOException $e) {
                // Just log the error, don't interrupt the user
                error_log('Error updating user activity: ' . $e->getMessage());
            }
        } elseif (!isset($_SESSION['last_db_activity'])) {
            $_SESSION['last_db_activity'] = time();
        }
        
        return true;
    }
    
    return false;
}

/**
 * Log out the current user
 */
function logoutUser(): void
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Log the logout action if user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Log logout action
        logUserAction(
            $userId,
            'logout',
            'user',
            $userId,
            'User logout'
        );
    }
    
    // Clear all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if the current logged-in user has admin role
 * 
 * @return bool True if the user is an admin, false otherwise
 */
function isAdmin(): bool
{
    // Must be logged in first
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check role in session
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // If role not in session, check from database
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        
        if ($result && $result['role'] === 'admin') {
            // Store in session for future checks
            $_SESSION['user_role'] = 'admin';
            return true;
        }
    } catch (PDOException $e) {
        error_log('Error checking admin role: ' . $e->getMessage());
    }
    
    return false;
}

/**
 * Redirect if the user is not an admin
 */
function redirectIfNotAdmin(): void
{
    if (!isAdmin()) {
        // Set error message
        setFlashMessage('Access denied. Admin privileges required.', 'danger');
        
        // Log unauthorized access attempt
        if (function_exists('logAuditEvent')) {
            logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page', 
                $_SESSION['user_id'] ?? 0, 'page', $_SERVER['REQUEST_URI'] ?? '');
        }
        
        // Redirect to home
        header('Location: index.php');
        exit;
    }
} 