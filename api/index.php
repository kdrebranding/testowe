<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

setCorsHeaders();

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$path = str_replace($basePath, '', $requestUri);
$path = strtok($path, '?'); // Remove query string
$path = trim($path, '/');

// Route to appropriate endpoint
switch ($path) {
    case 'login':
        require_once 'login.php';
        break;
    case 'stats':
        require_once 'stats.php';
        break;
    case 'users':
        require_once 'users.php';
        break;
    case 'access-requests':
        require_once 'access-requests.php';
        break;
    case 'applications':
        require_once 'applications.php';
        break;
    case 'orders':
        require_once 'orders.php';
        break;
    case 'issues':
        require_once 'issues.php';
        break;
    case 'files':
        require_once 'files.php';
        break;
    case 'stats/advanced':
        require_once 'advanced-stats.php';
        break;
    case 'backup/create':
        require_once 'backup-create.php';
        break;
    case 'backup/list':
        require_once 'backup-list.php';
        break;
    case 'activity-logs':
        require_once 'activity-logs.php';
        break;
    case 'payment-methods':
        require_once 'payment-methods.php';
        break;
    default:
        // Check if it's a backup delete request
        if (preg_match('/^backup\/(.+\.db)$/', $path, $matches)) {
            $_GET['filename'] = $matches[1];
            require_once 'backup-delete.php';
        }
        // Check if it's a file/user/order operation with ID
        elseif (preg_match('/^(files|users|orders|issues|payment-methods|access-requests)\/(\d+)(?:\/(approve|admin|reject))?$/', $path, $matches)) {
            $_GET['id'] = $matches[2];
            if (isset($matches[3])) {
                $_GET['action'] = $matches[3];
            }
            require_once $matches[1] . '.php';
        }
        // Check for activity logs cleanup
        elseif ($path === 'activity-logs/clear') {
            require_once 'activity-logs-clear.php';
        }
        else {
            http_response_code(404);
            jsonResponse(['error' => 'Endpoint not found']);
        }
        break;
}
?>

