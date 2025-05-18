<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to appropriate page based on role
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Initialize variables
$email = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission, please try again';
    } else {
        // Sanitize inputs
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Attempt to log in
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Set success flash message
            setFlashMessage('Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!', 'success');
            
            // Redirect to appropriate page based on role
            if (isAdmin()) {
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// HTML head and header
$pageTitle = 'Log In';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="text-center mb-4">
                <h1 class="h2 mb-3"><i class="bi bi-check2-square text-primary"></i> MyTasker</h1>
                <p class="text-muted">Sign in to your account to continue</p>
            </div>
            
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h2 class="h4 mb-0 text-center"><i class="bi bi-box-arrow-in-right"></i> Log In</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <!-- Email Field -->
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email); ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3">
                    <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary fw-bold">Register</a></p>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">PDPA Compliant &copy; <?php echo date('Y'); ?> MyTasker</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 