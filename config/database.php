<?php
/**
 * Database Connection Class
 * 
 * This class handles the database connection using PDO and loads
 * connection details from environment variables using phpdotenv.
 * 
 * @package    MyTasker
 * @author     CCS6344 Student
 */

// Include the bootstrap file
require_once __DIR__ . '/../bootstrap.php';

class Database {
    /**
     * PDO connection instance
     * 
     * @var \PDO
     */
    private $connection;
    
    /**
     * Database connection settings
     * 
     * @var array
     */
    private $settings = [];
    
    /**
     * Constructor - Initializes database connection
     * 
     * Loads environment variables from .env file and 
     * establishes a PDO connection to MySQL database
     */
    public function __construct() {
        // Set database connection settings from environment variables
        $this->settings = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'pass' => getenv('DB_PASS'),
            'charset' => 'utf8mb4'
        ];
        
        // Create the database connection
        $this->connect();
    }
    
    /**
     * Establishes the PDO connection with MySQL database
     * 
     * Configures PDO with appropriate attributes for error handling,
     * fetch mode, and prepared statement behavior
     * 
     * @return void
     * @throws \PDOException If connection fails
     */
    private function connect(): void {
        try {
            // Construct DSN (Data Source Name)
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->settings['host'],
                $this->settings['name'],
                $this->settings['charset']
            );
            
            // Set PDO options for secure and efficient connection
            $options = [
                // Error mode: throw exceptions for better error handling
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Default fetch mode: return associative arrays
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Disable emulation of prepared statements for real prepared statements
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Create new PDO instance
            $this->connection = new PDO(
                $dsn,
                $this->settings['user'],
                $this->settings['pass'],
                $options
            );
        } catch (PDOException $e) {
            // Re-throw the exception with a more user-friendly message
            throw new PDOException(
                'Database connection failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Returns the PDO connection instance
     * 
     * This method provides access to the active PDO connection
     * for executing queries and database operations
     * 
     * @return \PDO The active PDO connection instance
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
} 