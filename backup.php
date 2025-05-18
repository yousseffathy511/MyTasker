<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// 3) General functions including backup/restore functions
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

// Restrict access to admins only
if (!isAdmin()) {
    setFlashMessage('Access denied. Admin privileges required.', 'danger');
    
    // Log unauthorized access attempt
    if (function_exists('logAuditEvent')) {
        logAuditEvent('UNAUTHORIZED_ACCESS', 'User attempted to access admin-only page: backup.php');
    }
    
    header('Location: index.php');
    exit;
}

// Define backup directory
$backupDir = __DIR__ . '/backups';

// Create backup directory if it doesn't exist
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Process backup creation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid form submission, please try again', 'danger');
    } else {
        // Create backup using the function from functions.php
        $result = backup_database();
        
        if ($result['success']) {
            setFlashMessage('Backup created successfully: ' . $result['filename'], 'success');
        } else {
            setFlashMessage('Error creating backup: ' . ($result['message'] ?? 'Unknown error'), 'danger');
        }
    }
    
    // Redirect to refresh page
    header('Location: backup.php');
    exit;
}

// Scan for backup files
$backupFiles = [];
if (file_exists($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filePath = $backupDir . '/' . $file;
            $backupFiles[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath)
            ];
        }
    }
    
    // Sort by modified time (newest first)
    usort($backupFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// HTML head and header
$pageTitle = 'Database Backup & Restore';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="bi bi-database"></i> <?php echo $pageTitle; ?></h1>
            <p class="text-muted">Manage database backups for your task data</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Tasks
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Create New Backup</h5>
                </div>
                <div class="card-body">
                    <p>Create a backup of your current database with all tasks.</p>
                    <form method="POST" action="backup.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="create">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download"></i> Create Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> About Backups</h5>
                </div>
                <div class="card-body">
                    <p>Backups are important to prevent data loss. You can:</p>
                    <ul>
                        <li>Create backups anytime</li>
                        <li>Download them for offline storage</li>
                        <li>Restore from a previous backup if needed</li>
                    </ul>
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-exclamation-triangle"></i> Note: Restoring will overwrite all current data.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-list"></i> Available Backups</h5>
        </div>
        <div class="card-body">
            <?php if (empty($backupFiles)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No backup files found. Use the "Create Backup" button above to make your first backup.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backupFiles as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file['name']); ?></td>
                                    <td><?php echo formatFileSize($file['size']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
                                    <td class="text-nowrap">
                                        <a href="download_backup.php?file=<?php echo urlencode($file['name']); ?>&csrf_token=<?php echo generateCsrfToken(); ?>" 
                                           class="btn btn-sm btn-outline-primary me-1" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        
                                        <form method="POST" action="restore.php" class="d-inline me-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Restore" 
                                                    onclick="return confirm('Are you sure you want to restore this backup? This will OVERWRITE all your current data!')">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="delete_backup.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" 
                                                    onclick="return confirm('Are you sure you want to delete this backup file?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
/**
 * Format file size in human-readable form
 * 
 * @param int $bytes The file size in bytes
 * @return string The formatted file size
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes > 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Include footer
require_once 'includes/footer.php';
?> 