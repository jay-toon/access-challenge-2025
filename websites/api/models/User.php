<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email, $password) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email AND password = :password');
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password', $password);
        return $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare('
            INSERT INTO users (full_name, email, authority, password)
            VALUES (:full_name, :email, :authority, :password)
        ');
        
        $stmt->bindValue(':full_name', $data['fullName']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':authority', $data['authority']);
        $stmt->bindValue(':password', $data['password']);
        
        return $stmt->execute() ? $this->db->lastInsertRowID() : false;
    }
} 