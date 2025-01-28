<?php
class Response {
    public static function json($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    public static function error($status, $message) {
        self::json(['error' => $message], $status);
    }

    public static function success($data = null) {
        self::json(['success' => true, 'data' => $data]);
    }
} 