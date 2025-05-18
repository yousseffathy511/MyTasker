<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// 3) General functions (task related functions)
require_once __DIR__ . '/includes/functions.php';

// 4) Task-specific functions
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

// Get all tasks for the current user
$tasks = getUserTasks($userId);

// Count completed and pending tasks
$completedTasks = 0;
$pendingTasks = 0;
foreach ($tasks as $task) {
    if ($task['is_done']) {
        $completedTasks++;
    } else {
        $pendingTasks++;
    }
}

// HTML head and header
$pageTitle = 'My Tasks';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-list-check"></i> <?php echo $pageTitle; ?></h1>
                    <p class="text-muted">Manage your tasks and to-do items in one place</p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-primary btn-float">
                        <i class="bi bi-plus-circle"></i> Add New Task
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Task Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-value"><?php echo count($tasks); ?></div>
                            <div class="stat-card-label">Total Tasks</div>
                        </div>
                        <div>
                            <i class="bi bi-list-check" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-value"><?php echo $completedTasks; ?></div>
                            <div class="stat-card-label">Completed</div>
                        </div>
                        <div>
                            <i class="bi bi-check-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, orange 0%, #cc7000 100%); color: white;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-value"><?php echo $pendingTasks; ?></div>
                            <div class="stat-card-label">Pending</div>
                        </div>
                        <div>
                            <i class="bi bi-hourglass-split" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="text-center py-5">
            <i class="bi bi-clipboard-check text-muted" style="font-size: 4rem;"></i>
            <h2 class="mt-4">No Tasks Yet</h2>
            <p class="text-muted mb-4">You don't have any tasks yet. Start by creating your first task.</p>
            <a href="create.php" class="btn btn-lg btn-primary">
                <i class="bi bi-plus-circle"></i> Create Your First Task
            </a>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-primary text-white">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <h2 class="h5 mb-0">Your Task List</h2>
                </div>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($tasks as $task): ?>
                    <li class="list-group-item task-item <?php echo $task['is_done'] ? 'task-complete' : ''; ?>">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1 task-title">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </h5>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="mb-0 text-muted small">
                                        <?php echo htmlspecialchars($task['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <form action="update_status.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <div class="form-check">
                                        <input class="form-check-input task-status-toggle" type="checkbox" 
                                               id="task-<?php echo $task['id']; ?>" 
                                               <?php echo $task['is_done'] ? 'checked' : ''; ?> 
                                               onchange="this.form.submit()">
                                        <label class="form-check-label" for="task-<?php echo $task['id']; ?>">
                                            <?php if ($task['is_done']): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: orange;">Pending</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-2 text-muted small">
                                <i class="bi bi-calendar me-1"></i>
                                <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="delete.php" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Are you sure you want to delete this task?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php if (count($tasks) > 5): ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    // Add event listeners for task status toggles if needed
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.task-status-toggle');
        toggles.forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                // The form submission is handled by the onchange attribute
                // This is just a hook for any additional JavaScript logic
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 