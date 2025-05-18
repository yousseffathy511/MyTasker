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
    // Redirect to dashboard
    header('Location: index.php');
    exit;
}

// Initialize variables
$name = $email = '';
$dataRetentionApproved = false;
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission, please try again';
    } else {
        // Sanitize inputs
        $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $dataRetentionApproved = isset($_POST['data_retention']);
        
        // Check data retention policy acceptance
        if (!$dataRetentionApproved) {
            $errors[] = 'You must agree to the data retention policy';
        }
        
        // Validate password confirmation
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match';
        } else if (empty($errors)) {
            // Register the user
            $result = registerUser($name, $email, $password, $dataRetentionApproved);
            
            if ($result['success']) {
                // Set success flash message
                setFlashMessage('Registration successful! Please log in.', 'success');
                
                // Redirect to login page
                header('Location: login.php');
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// HTML head and header
$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Create Account</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register.php" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
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
                            <div class="form-text">We'll never share your email with anyone else</div>
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="8" required>
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
                        
                        <!-- Confirm Password Field -->
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" 
                                   name="password_confirm" required>
                        </div>
                        
                        <!-- Data Retention Policy -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="data_retention" 
                                   name="data_retention" required>
                            <label class="form-check-label" for="data_retention">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#pdpaModal">data retention policy</a> 
                                as required by PDPA 2010
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PDPA Modal -->
<div class="modal fade" id="pdpaModal" tabindex="-1" aria-labelledby="pdpaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdpaModalLabel">Data Retention Policy - PDPA 2010 Compliance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>Personal Data Protection Act 2010 (PDPA)</h5>
                <p>In accordance with the Personal Data Protection Act 2010 (PDPA) of Malaysia, we inform you about how we collect, use, and protect your personal information.</p>
                
                <h6>1. Collection and Usage</h6>
                <p>We collect your name and email address to:</p>
                <ul>
                    <li>Create and manage your account</li>
                    <li>Authenticate you when you log in</li>
                    <li>Associate your tasks with your account</li>
                    <li>Send essential communications regarding your account</li>
                </ul>
                
                <h6>2. Data Storage and Security</h6>
                <p>Your personal data is stored securely in our database with appropriate technical measures including:</p>
                <ul>
                    <li>Password encryption (hashing)</li>
                    <li>Access controls limiting who can view your information</li>
                    <li>Regular security audits and logging</li>
                </ul>
                
                <h6>3. Duration of Retention</h6>
                <p>Your personal data will be retained for as long as you maintain an active account with us. If your account becomes inactive for more than 2 years, we will send you a notification before deletion.</p>
                
                <h6>4. Your Rights</h6>
                <p>Under PDPA 2010, you have the right to:</p>
                <ul>
                    <li>Access your personal data that we store</li>
                    <li>Correct any inaccurate information</li>
                    <li>Request deletion of your account and associated data</li>
                    <li>Withdraw consent for data processing</li>
                </ul>
                
                <h6>5. Changes to Policy</h6>
                <p>Any changes to our data retention policy will be communicated to you via email.</p>
                
                <p class="mt-3 mb-0"><strong>By checking the consent box, you acknowledge that you have read, understood, and consent to these terms.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 