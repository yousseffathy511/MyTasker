<?php
// Include bootstrap file for autoloading and environment variables
require_once __DIR__ . '/../bootstrap.php';

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Security and validation functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Authentication functions
function register_user($name, $email, $password) {
    $db = connect_db();
    
    // Check if email already exists
    $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->execute([$email]);
    
    if ($check_stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $result = $stmt->execute([$name, $email, $password_hash]);
    
    if ($result) {
        return ['success' => true, 'user_id' => $db->lastInsertId()];
    } else {
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

function login_user($email, $password) {
    $db = connect_db();
    $stmt = $db->prepare("SELECT id, name, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $email;
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function logout_user() {
    // Unset all session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

// Task functions
function get_user_tasks($user_id) {
    $db = connect_db();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_task($task_id, $user_id) {
    $db = connect_db();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    return $stmt->fetch();
}

function create_task($user_id, $title, $description = "") {
    $db = connect_db();
    $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)");
    $result = $stmt->execute([$user_id, $title, $description]);
    
    if ($result) {
        return ['success' => true, 'task_id' => $db->lastInsertId()];
    } else {
        return ['success' => false, 'message' => 'Failed to create task'];
    }
}

function update_task($task_id, $user_id, $title, $description, $is_done) {
    $db = connect_db();
    $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, is_done = ? WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$title, $description, $is_done, $task_id, $user_id]);
    
    if ($result) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to update task'];
    }
}

function delete_task($task_id, $user_id) {
    $db = connect_db();
    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$task_id, $user_id]);
    
    if ($result) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to delete task'];
    }
}

// Backup/restore functions
function backup_database($filename = null) {
    if (!$filename) {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    }
    
    // Ensure logs directory exists
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    
    // Define log file for debugging
    $logFile = __DIR__ . '/../logs/backup_debug.log';
    
    // Start logging
    $log = "=== Backup started at " . date('Y-m-d H:i:s') . " ===\n";
    
    // Create backups directory if it doesn't exist
    $backupDir = __DIR__ . '/../backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
        $log .= "Created backups directory\n";
    }
    
    $output_file = $backupDir . '/' . $filename;
    $log .= "Output file: $output_file\n";
    
    // Get database credentials from environment
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbUser = getenv('DB_USER') ?: 'root';  // Default to root if not set
    $dbPass = getenv('DB_PASS') ?: '';      // Default to empty password
    $dbName = getenv('DB_NAME');
    
    $log .= "DB Host: $dbHost\n";
    $log .= "DB User: $dbUser\n";
    $log .= "DB Name: $dbName\n";
    
    // Verify we have the necessary credentials
    if (empty($dbName)) {
        $log .= "ERROR: Missing database name\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => 'Missing database name in environment variables'];
    }
    
    // XAMPP mysqldump path - use the absolute path
    $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';
    
    if (!file_exists($mysqldumpPath)) {
        $log .= "ERROR: Cannot find mysqldump at: $mysqldumpPath\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => "MySQL dump utility not found at: $mysqldumpPath"];
    }
    
    $log .= "Found mysqldump at: $mysqldumpPath\n";
    
    // Build the command with proper escaping
    if (empty($dbPass)) {
        $cmd = sprintf(
            'cmd.exe /c "%s -h%s -u%s %s > "%s""',
            $mysqldumpPath,
            $dbHost,
            $dbUser,
            $dbName,
            $output_file
        );
    } else {
        $cmd = sprintf(
            'cmd.exe /c "%s -h%s -u%s -p%s %s > "%s""',
            $mysqldumpPath,
            $dbHost,
            $dbUser,
            $dbPass,
            $dbName,
            $output_file
        );
    }
    
    // For security, remove password from log
    $logCmd = str_replace("-p$dbPass", "-p********", $cmd);
    $log .= "Executing command: $logCmd\n";
    
    // Execute the command
    $cmdOutput = [];
    $returnCode = 0;
    exec($cmd . " 2>&1", $cmdOutput, $returnCode);
    
    $log .= "Return code: $returnCode\n";
    if (!empty($cmdOutput)) {
        $log .= "Command output: " . implode("\n", $cmdOutput) . "\n";
    }
    
    if ($returnCode === 0) {
        // Verify the backup file exists and has content
        if (file_exists($output_file) && filesize($output_file) > 0) {
            $fileSize = filesize($output_file);
            $log .= "SUCCESS: Backup file created ($fileSize bytes)\n";
            file_put_contents($logFile, $log, FILE_APPEND);
            return ['success' => true, 'filename' => $filename];
        } else {
            $log .= "ERROR: Backup file is empty or not created\n";
            file_put_contents($logFile, $log, FILE_APPEND);
            return ['success' => false, 'message' => 'Backup file created but is empty or not found'];
        }
    } else {
        $errorMessage = !empty($cmdOutput) ? implode("\n", $cmdOutput) : 'Unknown error during backup';
        $log .= "ERROR: $errorMessage\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => $errorMessage];
    }
}

function restore_database($filename) {
    // Define log file for debugging
    $logFile = __DIR__ . '/../logs/restore_debug.log';
    
    // Start logging
    $log = "=== Restore started at " . date('Y-m-d H:i:s') . " ===\n";
    $log .= "Restoring from file: $filename\n";
    
    $input_file = __DIR__ . '/../backups/' . $filename;
    
    if (!file_exists($input_file)) {
        $log .= "ERROR: Backup file not found: $input_file\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => 'Backup file not found'];
    }
    
    if (filesize($input_file) === 0) {
        $log .= "ERROR: Backup file is empty\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => 'Backup file is empty'];
    }
    
    $log .= "Found backup file of size: " . filesize($input_file) . " bytes\n";
    
    // Get database credentials from environment
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbUser = getenv('DB_USER') ?: 'root';  // Default to root if not set
    $dbPass = getenv('DB_PASS') ?: '';      // Default to empty password
    $dbName = getenv('DB_NAME');
    
    $log .= "DB Host: $dbHost\n";
    $log .= "DB User: $dbUser\n";
    $log .= "DB Name: $dbName\n";
    
    // Verify we have the necessary credentials
    if (empty($dbName)) {
        $log .= "ERROR: Missing database name\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => 'Missing database name in environment variables'];
    }
    
    // XAMPP mysql path
    $mysqlPath = 'C:\xampp\mysql\bin\mysql.exe';
    
    if (!file_exists($mysqlPath)) {
        $log .= "ERROR: Cannot find mysql at: $mysqlPath\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => "MySQL client utility not found at: $mysqlPath"];
    }
    
    $log .= "Found mysql at: $mysqlPath\n";
    
    // Build the command with proper escaping
    if (empty($dbPass)) {
        $cmd = sprintf(
            'cmd.exe /c "%s -h%s -u%s %s < "%s""',
            $mysqlPath,
            $dbHost,
            $dbUser,
            $dbName,
            $input_file
        );
    } else {
        $cmd = sprintf(
            'cmd.exe /c "%s -h%s -u%s -p%s %s < "%s""',
            $mysqlPath,
            $dbHost,
            $dbUser,
            $dbPass,
            $dbName,
            $input_file
        );
    }
    
    // For security, remove password from log
    $logCmd = str_replace("-p$dbPass", "-p********", $cmd);
    $log .= "Executing command: $logCmd\n";
    
    // Execute the command
    $cmdOutput = [];
    $returnCode = 0;
    exec($cmd . " 2>&1", $cmdOutput, $returnCode);
    
    $log .= "Return code: $returnCode\n";
    if (!empty($cmdOutput)) {
        $log .= "Command output: " . implode("\n", $cmdOutput) . "\n";
    }
    
    if ($returnCode === 0) {
        $log .= "SUCCESS: Database restored successfully\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => true];
    } else {
        $errorMessage = !empty($cmdOutput) ? implode("\n", $cmdOutput) : 'Unknown error during restore';
        $log .= "ERROR: $errorMessage\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return ['success' => false, 'message' => $errorMessage];
    }
} 