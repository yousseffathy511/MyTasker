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

// Log profile view action
if (function_exists('logAuditEvent')) {
    logAuditEvent('VIEW_PERSONAL_DATA', 'User viewed personal profile');
}

// Initialize variables
$name = $_SESSION['user_name'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$messages = [];
$errors = [];

// Get user full details
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Log error and redirect
        setFlashMessage('User account not found', 'danger');
        header('Location: logout.php');
        exit;
    }
    
    // Load current values
    $name = $user['name'];
    $email = $user['email'];
} catch (PDOException $e) {
    $errors[] = 'Database error occurred';
}

// Process form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission, please try again';
    } else {
        // Sanitize inputs
        $updatedName = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $updatedEmail = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        // Simple validation
        if (empty($updatedName)) {
            $errors[] = 'Name is required';
        } elseif (strlen($updatedName) > 100) {
            $errors[] = 'Name must be 100 characters or less';
        }
        
        if (empty($updatedEmail)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($updatedEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } elseif (strlen($updatedEmail) > 150) {
            $errors[] = 'Email must be 150 characters or less';
        }
        
        // If email changed, check if it's already in use
        if ($updatedEmail !== $email) {
            try {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$updatedEmail, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Email address already registered by another user';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error occurred';
            }
        }
        
        // Update profile if no errors
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, last_activity = NOW() WHERE id = ?");
                $result = $stmt->execute([$updatedName, $updatedEmail, $userId]);
                
                if ($result) {
                    // Update session data
                    $_SESSION['user_name'] = $updatedName;
                    $_SESSION['user_email'] = $updatedEmail;
                    
                    // Log the update
                    if (function_exists('logAuditEvent')) {
                        logAuditEvent('UPDATE_PERSONAL_DATA', 'User updated profile information');
                    }
                    
                    $messages[] = 'Profile updated successfully';
                    
                    // Refresh page data
                    $name = $updatedName;
                    $email = $updatedEmail;
                } else {
                    $errors[] = 'Failed to update profile';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error occurred';
            }
        }
    }
}

// HTML head and header
$pageTitle = 'My Profile';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="bi bi-person-circle"></i> <?php echo $pageTitle; ?></h1>
            <p class="text-muted">View and manage your account information</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Tasks
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

    <div class="row">
        <!-- Profile Update Form -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0"><i class="bi bi-pencil"></i> Update Profile</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($name); ?>" 
                                   maxlength="100" required>
                            <div class="form-text">Maximum 100 characters</div>
                        </div>
                        
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   maxlength="150" required>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Account Management -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0"><i class="bi bi-gear"></i> Account Management</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Manage your account settings and data.</p>
                    
                    <!-- Change Password -->
                    <div class="d-grid gap-2 mb-3">
                        <a href="change_password.php" class="btn btn-outline-primary">
                            <i class="bi bi-key"></i> Change Password
                        </a>
                    </div>
                    
                    <!-- Delete Account -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-danger" 
                                data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="bi bi-trash"></i> Delete My Account
                        </button>
                    </div>
                    
                    <!-- Data Policy Info -->
                    <div class="mt-4">
                        <h6>Data Retention Policy</h6>
                        <p class="small text-muted">
                            Your data is being processed in accordance with our 
                            <a href="pdpa.php" target="_blank">PDPA policy</a>.
                        </p>
                        <p class="small text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Consent provided: <?php echo $user['data_retention_approved'] ? 'Yes' : 'No'; ?><br>
                            <i class="bi bi-calendar-event me-1"></i>
                            Consent date: <?php echo $user['data_retention_date'] ? date('Y-m-d', strtotime($user['data_retention_date'])) : 'N/A'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                <p>Deleting your account will:</p>
                <ul>
                    <li>Permanently remove all your personal information</li>
                    <li>Delete all your tasks and data</li>
                    <li>Log you out immediately</li>
                </ul>
                <p>Are you absolutely sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete_account.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <button type="submit" class="btn btn-danger">Delete My Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 