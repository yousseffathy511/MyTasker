<?php
// Load Composer autoloader and .env vars
require_once __DIR__ . '/../bootstrap.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get notifications count if user is logged in
$notificationsCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if notifications table exists
        $tables = $conn->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
        if (!empty($tables)) {
            // Count unread notifications for this user
            $stmt = $conn->prepare("
                SELECT COUNT(*) as unread_count
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = ?
                WHERE nr.id IS NULL
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $notificationsCount = $result['unread_count'] ?? 0;
        }
    } catch (PDOException $e) {
        // Silently handle any database errors
        error_log('Error getting notifications count: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>MyTasker</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="alternate icon" type="image/png" href="assets/img/favicon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo isAdmin() ? 'admin.php' : 'index.php'; ?>">
                <i class="bi bi-check2-square"></i> MyTasker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-person-circle"></i> 
                            Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <span class="badge bg-primary">Admin</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Admin Menu Items -->
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="backup.php">
                            <i class="bi bi-database"></i> Backups
                        </a>
                    </li>
                    <?php else: ?>
                    <!-- Regular User Menu Items -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="notifications.php">
                            <span style="position: relative; display: inline-block;">
                                <i class="bi bi-bell"></i> Notifications
                                <?php if ($notificationsCount > 0): ?>
                                <span class="badge bg-danger notification-badge"><?php echo $notificationsCount; ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create.php">
                            <i class="bi bi-plus-circle"></i> Add Task
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php 
        // Call the displayFlashMessages function if it exists
        if (function_exists('displayFlashMessages')) {
            displayFlashMessages();
        }
        // Fallback for backwards compatibility
        else if (isset($_SESSION['flash_message'])) {
            $type = $_SESSION['flash_type'] ?? 'info';
            $icon = 'info-circle';
            
            // Set appropriate icon based on message type
            switch ($type) {
                case 'success':
                    $icon = 'check-circle';
                    break;
                case 'danger':
                    $icon = 'exclamation-triangle';
                    break;
                case 'warning':
                    $icon = 'exclamation-circle';
                    break;
            }
            
            echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
            echo '<i class="bi bi-' . $icon . '"></i> ';
            echo htmlspecialchars($_SESSION['flash_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            
            // Clear the flash message
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        }
        ?>
    </div>
</body>
</html> 