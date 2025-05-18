<?php
/**
 * Data Retention Cleanup Script
 *
 * This script finds users who haven't been active for more than 2 years
 * and have approved data retention policy, then deletes their accounts.
 *
 * Recommended to run as a cron job, e.g.:
 * 0 0 1 * * php /path/to/taskmaker/scripts/cleanup_retention.php
 */

// Set to true for CLI output
define('VERBOSE', true);

// Define the root directory
$rootDir = dirname(__DIR__);

// Bootstrap the application
require_once $rootDir . '/bootstrap.php';

// Include database connection
require_once $rootDir . '/config/database.php';

// Include audit logging
if (file_exists($rootDir . '/includes/audit.php')) {
    require_once $rootDir . '/includes/audit.php';
}

/**
 * Log messages to console and file
 */
function logMessage($message) {
    if (VERBOSE) {
        echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    }
    error_log('[Data Retention] ' . $message);
}

/**
 * Send email notification to user about account deletion
 * Note: This is a placeholder - implement your actual email sending logic
 */
function sendDeletionNotification($email, $name) {
    // This is a placeholder for email sending
    // In a real application, you would implement actual email sending logic
    logMessage("Email notification would be sent to: $email");
    
    return true;
}

/**
 * Main execution function
 */
function runCleanup() {
    logMessage('Starting data retention cleanup process');
    
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Find users with last activity older than 2 years and who approved data retention
        $stmt = $conn->prepare("
            SELECT id, name, email 
            FROM users 
            WHERE data_retention_approved = 1
            AND last_activity < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        ");
        
        $stmt->execute();
        $inactiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalUsers = count($inactiveUsers);
        logMessage("Found $totalUsers inactive users eligible for deletion");
        
        // Process each user
        $deletedCount = 0;
        
        foreach ($inactiveUsers as $user) {
            logMessage("Processing user ID {$user['id']} ({$user['email']})");
            
            // Optional: Send notification email
            sendDeletionNotification($user['email'], $user['name']);
            
            // Log the deletion action
            if (function_exists('logAuditEvent')) {
                // Use a system user ID (e.g., 0) for the audit log
                logAuditEvent('AUTO_DELETE_ACCOUNT', 'Automated deletion due to retention policy', 0, 'user', $user['id']);
            }
            
            // Delete the user account
            // Foreign key constraints will handle cascading deletes
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $result = $deleteStmt->execute([$user['id']]);
            
            if ($result) {
                $deletedCount++;
                logMessage("Successfully deleted user ID {$user['id']}");
            } else {
                logMessage("Failed to delete user ID {$user['id']}");
            }
        }
        
        logMessage("Cleanup completed: $deletedCount/$totalUsers accounts processed");
        
    } catch (PDOException $e) {
        logMessage("ERROR: " . $e->getMessage());
    }
}

// Run the cleanup process
runCleanup(); 