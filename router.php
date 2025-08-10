<?php
// Router for PHP built-in server
// This handles URL rewriting that .htaccess would normally do

$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

// Remove query parameters for routing
$routePath = strtok($path, '?');

// Handle API routes
if (strpos($routePath, '/api/') === 0) {
    // Serve API requests through api/index.php
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    $_SERVER['PHP_SELF'] = '/api/index.php';
    
    // Adjust the REQUEST_URI for the API router
    $apiPath = substr($routePath, 4); // Remove '/api' prefix
    if (empty($apiPath)) {
        $apiPath = '/';
    }
    $_SERVER['REQUEST_URI'] = $apiPath . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
    
    require_once __DIR__ . '/api/index.php';
    return true;
}

// Handle admin routes
if (strpos($routePath, '/admin/') === 0) {
    $adminPath = substr($routePath, 7); // Remove '/admin/' prefix
    
    if (empty($adminPath) || $adminPath === '/') {
        // Serve index.html for /admin/ requests
        require_once __DIR__ . '/admin/index.html';
        return true;
    } else if (file_exists(__DIR__ . '/admin/' . $adminPath)) {
        // Serve static files from admin directory
        return false; // Let PHP built-in server handle static files
    } else {
        // Fallback to index.html for SPA routing
        require_once __DIR__ . '/admin/index.html';
        return true;
    }
}

// Handle root requests
if ($routePath === '/' || $routePath === '/index.php') {
    require_once __DIR__ . '/index.php';
    return true;
}

// For static files and other requests, let PHP built-in server handle them
return false;
?>"