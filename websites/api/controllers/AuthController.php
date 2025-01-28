<?php
class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            Response::error(400, 'Email and password required');
        }

        if (!str_ends_with($email, '.gov.uk')) {
            Response::error(400, 'Must use a .gov.uk email');
        }

        $user = $this->userModel->findByEmail($email, $password);
        if ($user) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'fullName' => $user['full_name'],
                'authority' => $user['authority']
            ];
            Response::success(['user' => $_SESSION['user']]);
        }

        Response::error(401, 'Invalid credentials');
    }

    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$this->validateRegistration($data)) {
            Response::error(400, 'Missing required fields');
        }

        try {
            if ($this->userModel->create($data)) {
                Response::success(['message' => 'User registered successfully']);
            }
        } catch(PDOException $e) {
            Response::error(400, 'Username or email already exists');
        }
    }

    private function validateRegistration($data) {
        return isset($data['username']) && 
               isset($data['password']) && 
               isset($data['email']);
    }
} 