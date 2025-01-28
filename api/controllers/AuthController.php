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
        
        if (!str_ends_with($data['email'], '.gov.uk')) {
            Response::error(400, 'Must use a .gov.uk email');
        }

        $userId = $this->userModel->create($data);
        if ($userId) {
            $_SESSION['user'] = [
                'id' => $userId,
                'email' => $data['email'],
                'fullName' => $data['fullName'],
                'authority' => $data['authority']
            ];
            Response::success(['user' => $_SESSION['user']]);
        }

        Response::error(500, 'Registration failed');
    }
} 