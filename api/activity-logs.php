<?php
// Phase 3 - Activity Logging System
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::authenticate();
$db = Database::getInstance();

$limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
$actionType = $_GET['action_type'] ?? null;

$sql = "
    SELECT al.*, u.username, u.first_name, u.last_name 
    FROM activity_logs al
    LEFT JOIN users u ON al.admin_id = u.id
";

$params = [];
if ($actionType) {
    $sql .= " WHERE al.action_type = ?";
    $params[] = $actionType;
}

$sql .= " ORDER BY al.created_at DESC LIMIT ?";
$params[] = $limit;

$logs = $db->fetchAll($sql, $params);

// Format response
$formattedLogs = array_map(function($log) {
    return [
        'id' => $log['id'],
        'admin_id' => $log['admin_id'],
        'action_type' => $log['action_type'],
        'resource_type' => $log['resource_type'],
        'resource_id' => $log['resource_id'],
        'description' => $log['description'],
        'ip_address' => $log['ip_address'],
        'user_agent' => $log['user_agent'],
        'created_at' => $log['created_at'],
        'admin' => $log['username'] ? [
            'username' => $log['username'],
            'first_name' => $log['first_name'],
            'last_name' => $log['last_name']
        ] : null
    ];
}, $logs);

jsonResponse($formattedLogs);
?>
/api/activity-logs-clear.php
<?php
// Phase 3 - Activity Logging System
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$adminId = Auth::authenticate();
$db = Database::getInstance();

$daysOlderThan = min(365, max(1, intval($_GET['days_older_than'] ?? 30)));
$cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOlderThan} days"));

$deletedCount = $db->execute("DELETE FROM activity_logs WHERE created_at < ?", [$cutoffDate]);

// Log this cleanup activity
logActivity($adminId, 'cleanup', "Cleared {$deletedCount} activity logs older than {$daysOlderThan} days");

jsonResponse([
    'success' => true,
    'message' => "Cleared {$deletedCount} activity logs older than {$daysOlderThan} days"
]);