<?php
require_once __DIR__ . '/../config/database.php';

// Set CORS headers
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, CORS_ALLOWED_ORIGINS) || DEBUG_MODE) {
        header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
    }
    header("Access-Control-Allow-Methods: " . implode(', ', CORS_ALLOWED_METHODS));
    header("Access-Control-Allow-Headers: " . implode(', ', CORS_ALLOWED_HEADERS));
    header("Access-Control-Allow-Credentials: true");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Send JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Get request body as JSON
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

// Log activity (Phase 3 - Activity Logging)
function logActivity($adminId, $actionType, $description, $resourceType = null, $resourceId = null, $ipAddress = null, $userAgent = null) {
    try {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO activity_logs (admin_id, action_type, resource_type, resource_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $db->execute($sql, [$adminId, $actionType, $resourceType, $resourceId, $description, $ipAddress, $userAgent]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
}

// Validate file upload
function validateFileUpload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
        return false;
    }
    
    return true;
}

// Create backup directory if not exists
function ensureBackupDirectory() {
    if (!is_dir(BACKUP_DIRECTORY)) {
        mkdir(BACKUP_DIRECTORY, 0755, true);
    }
}

// Clean old backups
function cleanOldBackups() {
    ensureBackupDirectory();
    
    $files = glob(BACKUP_DIRECTORY . '*.db');
    if (count($files) > MAX_BACKUPS_TO_KEEP) {
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $filesToDelete = array_slice($files, 0, count($files) - MAX_BACKUPS_TO_KEEP);
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }
}

// Get client IP address
function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = trim($_SERVER[$key]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Get user agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate pagination parameters
function validatePagination($page = 1, $limit = DEFAULT_PAGE_SIZE) {
    $page = max(1, intval($page));
    $limit = min(MAX_PAGE_SIZE, max(1, intval($limit)));
    $offset = ($page - 1) * $limit;
    
    return [$page, $limit, $offset];
}
?>