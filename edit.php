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

// Initialize variables
$errors = [];

// Get task ID from URL parameter
$taskId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$taskId) {
    // Invalid task ID
    setFlashMessage('Invalid task ID', 'danger');
    header('Location: index.php');
    exit;
}

// Get the task from database
$task = getTask($taskId, $userId);

if (!$task) {
    // Task not found or doesn't belong to current user
    setFlashMessage('Task not found or access denied', 'danger');
    header('Location: index.php');
    exit;
}

// Initialize form values with task data
$title = $task['title'];
$description = $task['description'];
$isDone = (bool)$task['is_done'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission, please try again';
    } else {
        // Sanitize inputs
        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $isDone = isset($_POST['is_done']);
        
        // Update the task
        $result = updateTask($taskId, $userId, $title, $description, $isDone);
        
        if ($result['success']) {
            // Set success flash message
            setFlashMessage('Task updated successfully', 'success');
            
            // Redirect to dashboard
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// HTML head and header
$pageTitle = 'Edit Task';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h2 class="h4 mb-0"><?php echo $pageTitle; ?></h2>
                    <a href="index.php" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Tasks
                    </a>
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
                    
                    <form method="POST" action="edit.php?id=<?php echo $taskId; ?>" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <!-- Title Field -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($title); ?>" 
                                   maxlength="200" required>
                            <div class="form-text">Maximum 200 characters</div>
                        </div>
                        
                        <!-- Description Field -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <!-- Completion Status -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_done" name="is_done" 
                                   <?php echo $isDone ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_done">Mark as completed</label>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 