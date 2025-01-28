<?php
class AuthMiddleware {
    public static function isAuthenticated() {
        if (!isset($_SESSION['user'])) {
            Response::error(401, 'Unauthorized');
            exit;
        }
        return true;
    }
} 