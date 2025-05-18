<?php
/**
 * Audit Logging Module
 * 
 * This file contains functions for logging user actions for audit purposes.
 * 
 * @package MyTasker
 */

// Include Database class
require_once __DIR__ . '/../config/Database.php';

/**
 * Log user action for auditing purposes
 * 
 * @param int $userId The ID of the user performing the action
 * @param string $action Type of action (login, create, update, delete, etc.)
 * @param string $resource Resource being accessed (user, task, etc.)
 * @param string $resourceId ID of the resource (optional)
 * @param string $details Additional information (optional)
 * @param string $ipAddress IP address of the user (optional)
 * @return bool True if logged successfully, false otherwise
 */
function logUserAction(
    int $userId, 
    string $action, 
    string $resource, 
    ?string $resourceId = null, 
    ?string $details = null,
    ?string $ipAddress = null
): bool {
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get IP address if not provided
        if ($ipAddress === null) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        // Prepare and execute the query
        $stmt = $conn->prepare("
            INSERT INTO audit_logs 
            (user_id, action, resource, resource_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId, 
            $action, 
            $resource, 
            $resourceId, 
            $details, 
            $ipAddress
        ]);
        
    } catch (PDOException $e) {
        // Log the error to server error log
        error_log('Error logging user action: ' . $e->getMessage());
        
        // Return false on error
        return false;
    }
}

/**
 * Get audit logs for reporting purposes
 * 
 * @param int|null $userId Filter by user ID (optional)
 * @param string|null $action Filter by action type (optional)
 * @param string|null $resource Filter by resource type (optional)
 * @param int $limit Maximum number of records to return
 * @param int $offset Offset for pagination
 * @return array Array of audit log records
 */
function getAuditLogs(
    ?int $userId = null, 
    ?string $action = null, 
    ?string $resource = null,
    int $limit = 100,
    int $offset = 0
): array {
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Start building the query
        $sql = "SELECT al.*, u.name as user_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Add conditions based on filters
        if ($userId !== null) {
            $sql .= " AND al.user_id = ?";
            $params[] = $userId;
        }
        
        if ($action !== null) {
            $sql .= " AND al.action = ?";
            $params[] = $action;
        }
        
        if ($resource !== null) {
            $sql .= " AND al.resource = ?";
            $params[] = $resource;
        }
        
        // Add sorting and pagination
        $sql .= " ORDER BY al.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        // Prepare and execute the query
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        // Log the error
        error_log('Error retrieving audit logs: ' . $e->getMessage());
        
        // Return empty array on error
        return [];
    }
} 