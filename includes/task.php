<?php
/**
 * Task Management Module
 * 
 * This file contains functions for managing tasks including 
 * creating, reading, updating, and deleting tasks.
 * 
 * @package MyTasker
 */

// Include Database class
require_once __DIR__ . '/../config/Database.php';

/**
 * Get all tasks for a specific user
 * 
 * @param int $userId The ID of the user
 * @return array An array of task records
 */
function getUserTasks(int $userId): array
{
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Prepare and execute the query
        $stmt = $conn->prepare("
            SELECT * FROM tasks 
            WHERE user_id = ? 
            ORDER BY is_done ASC, created_at DESC
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Log the error in a production environment
        error_log('Error fetching tasks: ' . $e->getMessage());
        
        // Return empty array on error
        return [];
    }
}

/**
 * Get a specific task by ID and user ID
 * 
 * @param int $taskId The ID of the task
 * @param int $userId The ID of the user (for security verification)
 * @return array|null The task record or null if not found
 */
function getTask(int $taskId, int $userId): ?array
{
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Prepare and execute the query
        $stmt = $conn->prepare("
            SELECT * FROM tasks 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([$taskId, $userId]);
        $task = $stmt->fetch();
        
        // Return the task or null if not found
        return $task ?: null;
        
    } catch (PDOException $e) {
        // Log the error in a production environment
        error_log('Error fetching task: ' . $e->getMessage());
        
        // Return null on error
        return null;
    }
}

/**
 * Create a new task
 * 
 * @param int $userId The ID of the user
 * @param string $title The title of the task
 * @param string $description The description of the task (optional)
 * @return array Result with success status and message or task ID
 */
function createTask(int $userId, string $title, string $description = ''): array
{
    // Validate title
    if (empty(trim($title))) {
        return ['success' => false, 'message' => 'Task title is required'];
    }
    
    if (strlen($title) > 200) {
        return ['success' => false, 'message' => 'Task title must be 200 characters or less'];
    }
    
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Prepare and execute the query
        $stmt = $conn->prepare("
            INSERT INTO tasks (user_id, title, description) 
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([$userId, $title, $description]);
        
        if ($result) {
            $taskId = $conn->lastInsertId();
            return ['success' => true, 'taskId' => $taskId];
        } else {
            return ['success' => false, 'message' => 'Failed to create task'];
        }
        
    } catch (PDOException $e) {
        // Log the error in a production environment
        error_log('Error creating task: ' . $e->getMessage());
        
        // Return error message
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Update an existing task
 * 
 * @param int $taskId The ID of the task
 * @param int $userId The ID of the user (for security verification)
 * @param string $title The title of the task
 * @param string $description The description of the task
 * @param bool $isDone The completion status
 * @return array Result with success status and message
 */
function updateTask(int $taskId, int $userId, string $title, string $description = '', bool $isDone = false): array
{
    // Validate title
    if (empty(trim($title))) {
        return ['success' => false, 'message' => 'Task title is required'];
    }
    
    if (strlen($title) > 200) {
        return ['success' => false, 'message' => 'Task title must be 200 characters or less'];
    }
    
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // First verify task belongs to user
        $checkStmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$taskId, $userId]);
        
        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Task not found or access denied'];
        }
        
        // Prepare and execute the update
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, is_done = ? 
            WHERE id = ? AND user_id = ?
        ");
        
        $isDoneInt = $isDone ? 1 : 0;
        $result = $stmt->execute([$title, $description, $isDoneInt, $taskId, $userId]);
        
        if ($result) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to update task'];
        }
        
    } catch (PDOException $e) {
        // Log the error in a production environment
        error_log('Error updating task: ' . $e->getMessage());
        
        // Return error message
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Delete a task
 * 
 * @param int $taskId The ID of the task
 * @param int $userId The ID of the user (for security verification)
 * @return array Result with success status and message
 */
function deleteTask(int $taskId, int $userId): array
{
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // First verify task belongs to user
        $checkStmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$taskId, $userId]);
        
        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Task not found or access denied'];
        }
        
        // Prepare and execute the delete
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$taskId, $userId]);
        
        if ($result) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to delete task'];
        }
        
    } catch (PDOException $e) {
        // Log the error in a production environment
        error_log('Error deleting task: ' . $e->getMessage());
        
        // Return error message
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Toggle the completion status of a task
 * 
 * @param int $taskId The ID of the task
 * @param int $userId The ID of the user (for security verification)
 * @return array Result with success status and message
 */
function toggleTaskStatus(int $taskId, int $userId): array
{
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // First get the current task
        $task = getTask($taskId, $userId);
        
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found or access denied'];
        }
        
        // Toggle the status
        $newStatus = !$task['is_done'];
        
        // Update the task
        return updateTask(
            $taskId, 
            $userId, 
            $task['title'], 
            $task['description'], 
            $newStatus
        );
        
    } catch (PDOException $e) {
        // Log the error in a production environment
        error_log('Error toggling task status: ' . $e->getMessage());
        
        // Return error message
        return ['success' => false, 'message' => 'Database error occurred'];
    }
} 