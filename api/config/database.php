<?php
class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = new SQLite3(__DIR__ . '/../../database.sqlite');
        $this->initTables();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    private function initTables() {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT,
                email TEXT UNIQUE,
                authority TEXT,
                password TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS suppliers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                share_code TEXT,
                supplier_data TEXT,
                status TEXT DEFAULT "pending",
                reviewed_by TEXT,
                rejection_reason TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }
} 