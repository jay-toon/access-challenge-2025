<?php
class User {
    private $db;
    private $table = 'users';

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
        $query = "INSERT INTO {$this->table} (username, password, email) VALUES (:username, :password, :email)";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':username' => $data['username'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':email' => $data['email']
        ]);
    }

    public function getAll() {
        $query = "SELECT id, username, email, created_at FROM {$this->table}";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 