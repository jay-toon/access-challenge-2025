<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        'controllers/',
        'models/',
        'middleware/',
        'utils/',
        'config/'
    ];

    foreach ($paths as $path) {
        $file = __DIR__ . '/' . $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Parse URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

// Basic routing
$route = $uri[0] ?? '';
$action = $uri[1] ?? '';

// Initialize controllers
$authController = new AuthController();

// Routes
switch($route) {
    case '':
        Response::success([
            'message' => 'API is working!',
            'version' => '1.0',
            'endpoints' => [
                'POST /auth/register' => 'Register new user',
                'GET /users' => 'List all users'
            ]
        ]);
        break;

    case 'auth':
        if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        }
        break;

    default:
        Response::error(404, 'Endpoint not found');
} 