<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Show API documentation for root endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'API is working!',
        'version' => '1.0',
        'available_endpoints' => [
            'GET /' => 'API Information',
            'POST /auth/register' => 'Register new user',
            'POST /auth/login' => 'Login user',
            'GET /users' => 'List all users (requires auth)',
            'GET /users/{id}' => 'Get user details (requires auth)',
            'POST /posts' => 'Create new post (requires auth)',
            'GET /posts' => 'List all posts',
            'GET /posts/{id}' => 'Get post details'
        ]
    ]);
    exit;
}

// Database connection
try {
    $db = new PDO(
        "mysql:host=mysql;dbname=development",
        "developer",
        "developer",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Initialize database tables
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
");

// Parse the URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

// Basic routing
$route = $uri[0] ?? '';
$action = $uri[1] ?? '';
$id = $uri[2] ?? null;

switch($route) {
    case 'auth':
        if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }

            try {
                $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                $stmt->execute([
                    $data['username'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $data['email']
                ]);
                echo json_encode(['message' => 'User registered successfully']);
            } catch(PDOException $e) {
                http_response_code(400);
                echo json_encode(['error' => 'Username or email already exists']);
            }
        }
        break;

    case 'users':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $stmt = $db->query("SELECT id, username, email, created_at FROM users");
            echo json_encode(['users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        break;

    case 'posts':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action) {
                $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
                $stmt->execute([$action]);
                echo json_encode(['post' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            } else {
                $stmt = $db->query("SELECT * FROM posts ORDER BY created_at DESC");
                echo json_encode(['posts' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO posts (title, content) VALUES (?, ?)");
            $stmt->execute([$data['title'], $data['content']]);
            echo json_encode(['message' => 'Post created successfully']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
} 