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

            // Posts table (example of additional table)
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ");

        } catch(PDOException $e) {
            Response::error(500, 'Failed to initialize database tables: ' . $e->getMessage());
        }
    }
} 