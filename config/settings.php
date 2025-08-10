<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

// JWT Settings
define('JWT_SECRET_KEY', 'your-secret-key-change-this-in-production-2024');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 3600 * 24); // 24 hours

// Admin credentials (change in production)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 300); // 5 minutes

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'apk']);
define('UPLOAD_DIRECTORY', __DIR__ . '/../uploads/');

// Pagination settings
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Activity log settings
define('ACTIVITY_LOG_RETENTION_DAYS', 30);

// Backup settings
define('BACKUP_DIRECTORY', __DIR__ . '/../backups/');
define('MAX_BACKUPS_TO_KEEP', 10);

// CORS settings
define('CORS_ALLOWED_ORIGINS', ['http://localhost:3000', 'http://localhost:8080']);
define('CORS_ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
define('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization']);

// Timezone
define('DEFAULT_TIMEZONE', 'Europe/Warsaw');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error reporting (disable in production)
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}
?>