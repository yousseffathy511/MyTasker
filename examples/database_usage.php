<?php
/**
 * Database Usage Example
 * 
 * This file demonstrates how to use the Database class to connect
 * to MySQL and perform basic CRUD operations.
 */

// Load Composer autoloader and .env vars
require_once __DIR__ . '/../bootstrap.php';

try {
    // Create a new Database instance
    $database = new Database();
    
    // Get the PDO connection
    $conn = $database->getConnection();
    
    echo "Database connection successful!\n";
    
    // Example: Select all users
    $stmt = $conn->query("SELECT id, name, email, created_at FROM users");
    $users = $stmt->fetchAll();
    
    echo "Users found: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "- {$user['name']} ({$user['email']})\n";
    }
    
    // Example: Prepared statement with parameters
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ?");
    $userId = 1; // Example user ID
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll();
    
    echo "\nTasks for user ID {$userId}: " . count($tasks) . "\n";
    foreach ($tasks as $task) {
        $status = $task['is_done'] ? 'Completed' : 'Pending';
        echo "- {$task['title']} [{$status}]\n";
    }
    
    // Example: Insert a new task (transaction example)
    $conn->beginTransaction();
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO tasks (user_id, title, description) 
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $userId,
            'Example Task from Database class',
            'This task was created using the new Database class with PDO'
        ]);
        
        if ($result) {
            $taskId = $conn->lastInsertId();
            echo "\nNew task inserted with ID: {$taskId}\n";
            $conn->commit();
        } else {
            $conn->rollBack();
            echo "\nFailed to insert task\n";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "\nTransaction failed: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
} 