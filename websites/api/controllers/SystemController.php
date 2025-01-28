<?php
class SystemController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStatus() {
        try {
            // Test database connection
            $dbStatus = $this->db->query('SELECT 1')->fetch();
            $dbConnected = !empty($dbStatus);

            // Get all tables
            $tables = [];
            $tablesQuery = $this->db->query("SHOW TABLES");
            while ($table = $tablesQuery->fetch(PDO::FETCH_COLUMN)) {
                $schema = [];
                $columnsQuery = $this->db->query("DESCRIBE " . $table);
                while ($column = $columnsQuery->fetch(PDO::FETCH_ASSOC)) {
                    $schema[] = [
                        'field' => $column['Field'],
                        'type' => $column['Type'],
                        'null' => $column['Null'],
                        'key' => $column['Key'],
                        'default' => $column['Default'],
                        'extra' => $column['Extra']
                    ];
                }
                
                // Get row count for each table
                $countQuery = $this->db->query("SELECT COUNT(*) as count FROM " . $table);
                $count = $countQuery->fetch(PDO::FETCH_ASSOC)['count'];

                $tables[$table] = [
                    'schema' => $schema,
                    'row_count' => $count
                ];
            }

            Response::success([
                'system_status' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'php_version' => PHP_VERSION,
                    'memory_usage' => $this->getMemoryUsage()
                ],
                'database_status' => [
                    'connected' => $dbConnected,
                    'server_version' => $this->db->getAttribute(PDO::ATTR_SERVER_VERSION),
                    'server_info' => $this->db->getAttribute(PDO::ATTR_SERVER_INFO),
                    'tables' => $tables
                ]
            ]);

        } catch (PDOException $e) {
            Response::error(500, 'Database error: ' . $e->getMessage());
        }
    }

    private function getMemoryUsage() {
        $mem = memory_get_usage(true);
        if ($mem < 1024) {
            return $mem . ' bytes';
        } elseif ($mem < 1048576) {
            return round($mem / 1024, 2) . ' KB';
        } else {
            return round($mem / 1048576, 2) . ' MB';
        }
    }
} 