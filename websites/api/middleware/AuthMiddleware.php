<?php
class AuthMiddleware {
    public static function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            Response::error(401, 'Unauthorized access');
            exit;
        }
        return true;
    }

    public static function validateApiKey() {
        $headers = getallheaders();
        if (!isset($headers['X-API-Key']) || $headers['X-API-Key'] !== 'your_api_key') {
            Response::error(401, 'Invalid API key');
            exit;
        }
        return true;
    }
} 