<?php
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    
    public static function authenticate() {
        // Try multiple ways to get Authorization header (Apache config dependent)
        $authHeader = null;
        
        // Method 1: getallheaders() (if available and working)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }
        
        // Method 2: $_SERVER array (most reliable with .htaccess rewrite rules)
        if (!$authHeader) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        }
        
        // Method 3: PHP's apache_request_headers() as fallback
        if (!$authHeader && function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            $authHeader = $apacheHeaders['Authorization'] ?? $apacheHeaders['authorization'] ?? null;
        }
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid authorization header']);
            exit;
        }
        
        $token = $matches[1];
        $payload = JWT::decode($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }
        
        return $payload['admin_id'] ?? null;
    }
    
    public static function login($username, $password, $ip_address = null, $user_agent = null) {
        if ($username !== ADMIN_USERNAME || $password !== ADMIN_PASSWORD) {
            return false;
        }
        
        $payload = ['admin_id' => 1, 'username' => $username];
        $token = JWT::encode($payload);
        
        // Log login activity
        logActivity(1, 'login', "Admin user {$username} logged in", null, null, $ip_address, $user_agent);
        
        return $token;
    }
    
    public static function getCurrentUserId() {
        return self::authenticate();
    }
}
?>