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
$id = $uri[2] ?? null;

// Initialize controllers and models
$authController = new AuthController();
$systemController = new SystemController();
$supplierModel = new Supplier();

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
                    'Authentication' => [
                        'POST /auth/register' => 'Register new user',
                        'POST /auth/login' => 'Login user'
                    ],
                    'Users' => [
                        'GET /users' => 'List all users',
                        'GET /users/{id}' => 'Get specific user'
                    ],
                    'Suppliers' => [
                        'POST /suppliers/create' => [
                            'description' => 'Create new supplier',
                            'body' => [
                                'share_code' => 'string',
                                'supplier_data' => 'object'
                            ]
                        ],
                        'GET /suppliers/pending' => 'List all pending suppliers',
                        'GET /suppliers/{id}' => 'Get specific supplier',
                        'POST /suppliers/status' => [
                            'description' => 'Update supplier status',
                            'body' => [
                                'id' => 'integer',
                                'status' => 'string (pending|approved|rejected)',
                                'rejection_reason' => 'string (optional)'
                            ]
                        ]
                    ],
                    'System' => [
                        'GET /' => 'API information and system status'
                    ]
                ],
                'example_requests' => [
                    'Create Supplier' => [
                        'curl' => 'curl -X POST http://localhost/api/suppliers/create \
-H "Content-Type: application/json" \
-d \'{"share_code": "ABC123", "supplier_data": {"name": "Test Supplier"}}\'',
                        'response' => ['id' => 1]
                    ],
                    'Update Status' => [
                        'curl' => 'curl -X POST http://localhost/api/suppliers/status \
-H "Content-Type: application/json" \
-d \'{"id": 1, "status": "approved"}\'',
                        'response' => ['message' => 'Status updated successfully']
                    ]
                ]
            ]);
        } catch (Exception $e) {
            Response::error(500, 'System status check failed: ' . $e->getMessage());
        }
        break;

    case 'auth':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            switch($action) {
                case 'register':
                    $authController->register();
                    break;
                case 'login':
                    $authController->login();
                    break;
                default:
                    Response::error(404, 'Invalid auth endpoint');
            }
        } else {
            Response::error(405, 'Method not allowed');
        }
        break;

    case 'suppliers':
        switch($action) {
            case '':
                // GET /suppliers - List all suppliers
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    try {
                        $suppliers = $supplierModel->getAll();
                        Response::success(['suppliers' => $suppliers]);
                    } catch(Exception $e) {
                        Response::error(500, $e->getMessage());
                    }
                } else {
                    Response::error(405, 'Method not allowed');
                }
                break;

            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    try {
                        $data = json_decode(file_get_contents('php://input'), true);
                        if (!$data) {
                            Response::error(400, 'Invalid JSON data');
                            break;
                        }
                        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                        $supplierId = $supplierModel->create($data, $userId);
                        Response::success(['id' => $supplierId, 'message' => 'Supplier created successfully']);
                    } catch(Exception $e) {
                        Response::error(500, $e->getMessage());
                    }
                } else {
                    Response::error(405, 'Method not allowed');
                }
                break;

            case 'pending':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    try {
                        $pendingSuppliers = $supplierModel->getPending();
                        Response::success(['suppliers' => $pendingSuppliers]);
                    } catch(Exception $e) {
                        Response::error(500, $e->getMessage());
                    }
                } else {
                    Response::error(405, 'Method not allowed');
                }
                break;

            case 'status':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    try {
                        $data = json_decode(file_get_contents('php://input'), true);
                        if (!$data || !isset($data['id']) || !isset($data['status'])) {
                            Response::error(400, 'Missing required fields');
                            break;
                        }
                        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                        $supplierModel->updateStatus(
                            $data['id'],
                            $data['status'],
                            $userId,
                            $data['rejection_reason'] ?? null
                        );
                        Response::success(['message' => 'Status updated successfully']);
                    } catch(Exception $e) {
                        Response::error(500, $e->getMessage());
                    }
                } else {
                    Response::error(405, 'Method not allowed');
                }
                break;

            default:
                // Handle GET /suppliers/{id}
                if (is_numeric($action)) {
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        try {
                            $supplier = $supplierModel->getById($action);
                            if ($supplier) {
                                Response::success(['supplier' => $supplier]);
                            } else {
                                Response::error(404, 'Supplier not found');
                            }
                        } catch(Exception $e) {
                            Response::error(500, $e->getMessage());
                        }
                    } else {
                        Response::error(405, 'Method not allowed');
                    }
                } else {
                    Response::error(404, 'Invalid endpoint');
                }
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