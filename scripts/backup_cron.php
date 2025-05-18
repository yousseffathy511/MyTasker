<?php
/**
 * Automated Database Backup Script
 * 
 * This script performs automated backups of the TaskMaker database.
 * It should be run as a cron job.
 * 
 * Example cron entry (daily at 2am):
 * 0 2 * * * php /path/to/taskmaker/scripts/backup_cron.php > /dev/null 2>&1
 * 
 * @package MyTasker
 */

// Set to true for CLI output, false for cron job
define('VERBOSE', false);

// Define constants
define('MAX_BACKUPS', 7); // Number of backups to keep (1 week worth)

// Load environment
require_once __DIR__ . '/../bootstrap.php';

// Include Database class
require_once __DIR__ . '/../config/Database.php';

/**
 * Log messages to console or file
 */
function log_message(string $message): void {
    if (VERBOSE) {
        echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    }
    error_log('[Backup] ' . $message);
}

/**
 * Perform the database backup
 */
function perform_backup(): bool {
    log_message('Starting database backup process');
    
    try {
        // Get database credentials from environment
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_NAME');
        $dbUser = getenv('DB_USER');
        $dbPass = getenv('DB_PASS');
        
        // Check if credentials are available
        if (empty($dbName) || empty($dbUser)) {
            log_message('ERROR: Database credentials not found in environment variables');
            return false;
        }
        
        // Create backup directory if it doesn't exist
        $backupDir = __DIR__ . '/../backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Generate backup filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "$backupDir/{$dbName}_backup_$timestamp.sql";
        
        // Construct mysqldump command with password handling
        if (empty($dbPass)) {
            // No password
            $command = "mysqldump -h$dbHost -u$dbUser $dbName > \"$backupFile\"";
        } else {
            // With password - avoid exposing it in process list
            $tempPasswordFile = tempnam(sys_get_temp_dir(), 'mysqlpass');
            file_put_contents($tempPasswordFile, "[client]\npassword=\"$dbPass\"\n");
            $command = "mysqldump -h$dbHost -u$dbUser --defaults-extra-file=\"$tempPasswordFile\" $dbName > \"$backupFile\"";
        }
        
        // Execute backup command
        log_message("Executing backup to file: $backupFile");
        
        // Add 2>&1 to capture errors
        exec($command . " 2>&1", $output, $returnCode);
        
        // Clean up temp password file if used
        if (!empty($dbPass)) {
            unlink($tempPasswordFile);
        }
        
        // Check if backup was successful
        if ($returnCode !== 0) {
            log_message('ERROR: Backup failed with code ' . $returnCode);
            log_message('Error output: ' . implode("\n", $output));
            return false;
        }
        
        // Compress the backup file
        log_message('Compressing backup file');
        exec("gzip \"$backupFile\"", $output, $returnCode);
        
        if ($returnCode === 0) {
            log_message('Backup compressed successfully');
            $backupFile .= '.gz';
        } else {
            log_message('WARNING: Failed to compress backup file, but backup was created');
        }
        
        // Remove old backups beyond MAX_BACKUPS
        cleanup_old_backups($backupDir);
        
        log_message('Backup completed successfully');
        return true;
        
    } catch (Exception $e) {
        log_message('ERROR: Exception during backup: ' . $e->getMessage());
        return false;
    }
}

/**
 * Clean up old backups beyond MAX_BACKUPS
 */
function cleanup_old_backups(string $backupDir): void {
    log_message('Cleaning up old backups');
    
    // Get list of backup files
    $files = glob("$backupDir/*_backup_*.sql*");
    
    // Sort files by modification time (oldest first)
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // Remove oldest files if we have more than MAX_BACKUPS
    $filesToRemove = count($files) - MAX_BACKUPS;
    if ($filesToRemove > 0) {
        log_message("Removing $filesToRemove old backup(s)");
        
        for ($i = 0; $i < $filesToRemove; $i++) {
            if (file_exists($files[$i])) {
                unlink($files[$i]);
                log_message("Removed: " . basename($files[$i]));
            }
        }
    } else {
        log_message('No old backups to remove');
    }
}

// Execute the backup
perform_backup(); 