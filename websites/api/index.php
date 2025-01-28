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

// Remove 'api' from the URI if it exists
if ($uri[0] === 'api') {
    array_shift($uri);
}

// Get route parts
$route = $uri[0] ?? '';
$action = $uri[1] ?? '';

// Initialize controllers
$authController = new AuthController();
$systemController = new SystemController();

// Routes
switch($route) {
    case '':
        // Root endpoint - show API documentation and system status
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get database status
            $dbConnected = false;
            $dbVersion = null;
            $tables = [];
            
            try {
                $dbConnected = $db->query('SELECT 1')->fetch() ? true : false;
                $dbVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
                
                // Get tables info
                $tablesQuery = $db->query("SHOW TABLES");
                while ($table = $tablesQuery->fetch(PDO::FETCH_COLUMN)) {
                    $countQuery = $db->query("SELECT COUNT(*) as count FROM " . $table);
                    $count = $countQuery->fetch(PDO::FETCH_ASSOC)['count'];
                    $tables[$table] = [
                        'rows' => $count
                    ];
                }
            } catch (PDOException $e) {
                $dbConnected = false;
            }

            Response::success([
                'api_info' => [
                    'status' => 'online',
                    'version' => '1.0',
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
                'system_status' => [
                    'php_version' => PHP_VERSION,
                    'memory_usage' => formatBytes(memory_get_usage(true))
                ],
                'database_status' => [
                    'connected' => $dbConnected,
                    'version' => $dbVersion,
                    'tables' => $tables
                ],
                'available_endpoints' => [
                    'POST /auth/register' => 'Register new user',
                    'POST /auth/login' => 'Login user',
                    'GET /users' => 'List all users'
                ]
            ]);
        } catch (Exception $e) {
            Response::error(500, 'System status check failed: ' . $e->getMessage());
        }
        break;

    case 'auth':
        if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        }
        break;

    default:
        Response::error(404, 'Endpoint not found');
}

// Helper function to format bytes
function formatBytes($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' bytes';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return round($bytes / 1048576, 2) . ' MB';
    }
} 