<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=mysql;dbname=development",
                "developer",
                "developer",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->initTables();
        } catch(PDOException $e) {
            Response::error(500, 'Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    private function initTables() {
        try {
            // Users table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Suppliers table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS suppliers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    share_code VARCHAR(100) UNIQUE,
                    supplier_data JSON,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    reviewed_by INT,
                    rejection_reason TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (reviewed_by) REFERENCES users(id),
                    INDEX idx_status (status),
                    INDEX idx_share_code (share_code)
                )
            ");

            // Audit log table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS audit_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    action VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    old_value JSON,
                    new_value JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    INDEX idx_entity (entity_type, entity_id)
                )
            ");

        } catch(PDOException $e) {
            Response::error(500, 'Failed to initialize database tables: ' . $e->getMessage());
        }
    }
} 