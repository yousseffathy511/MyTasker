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

// Get current user data
$userId = $_SESSION['user_id'];
$database = new Database();
$conn = $database->getConnection();

// Initialize variables
$errors = [];
$messages = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission, please try again';
    } else {
        // Get form data
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate current password
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        } else {
            // Verify current password
            try {
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $errors[] = 'Current password is incorrect';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error occurred';
            }
        }
        
        // Validate new password
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'New password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = 'New password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'New password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $errors[] = 'New password must contain at least one special character';
        }
        
        // Validate password confirmation
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        // Update password if no errors
        if (empty($errors)) {
            try {
                // Hash the new password
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update the password in the database
                $stmt = $conn->prepare("UPDATE users SET password_hash = ?, last_activity = NOW() WHERE id = ?");
                $result = $stmt->execute([$passwordHash, $userId]);
                
                if ($result) {
                    // Log the password change
                    if (function_exists('logAuditEvent')) {
                        logAuditEvent('PASSWORD_CHANGE', 'User changed their password');
                    }
                    
                    $messages[] = 'Password updated successfully';
                } else {
                    $errors[] = 'Failed to update password';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error occurred';
            }
        }
    }
}

// HTML head and header
$pageTitle = 'Change Password';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="bi bi-key"></i> <?php echo $pageTitle; ?></h1>
            <p class="text-muted">Update your account password</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="profile.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Profile
            </a>
        </div>
    </div>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0"><i class="bi bi-lock"></i> Update Password</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="change_password.php" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <!-- Current Password Field -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <!-- New Password Field -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">
                                Password must:
                                <ul class="small ps-3 mb-0">
                                    <li>Be at least 8 characters long</li>
                                    <li>Include at least one uppercase letter</li>
                                    <li>Include at least one lowercase letter</li>
                                    <li>Include at least one number</li>
                                    <li>Include at least one special character</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Confirm New Password Field -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 