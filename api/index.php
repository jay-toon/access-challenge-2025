<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

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

// Router
$request_method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/', '', $path);

$authController = new AuthController();
$supplierController = new SupplierController();

// Routes
switch ($path) {
    case 'login':
        if ($request_method === 'POST') {
            $authController->login();
        }
        break;

    case 'register':
        if ($request_method === 'POST') {
            $authController->register();
        }
        break;

    case 'suppliers/pending':
        if ($request_method === 'GET') {
            $supplierController->getPendingSuppliers();
        }
        break;

    case (preg_match('/^suppliers\/(\d+)\/decision$/', $path, $matches) ? true : false):
        if ($request_method === 'POST') {
            $supplierController->updateDecision($matches[1]);
        }
        break;

    default:
        Response::error(404, 'Not found');
        break;
} 