<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::authenticate();

$db = Database::getInstance();

// Get basic stats
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$pendingAccessRequests = $db->fetchOne("SELECT COUNT(*) as count FROM access_requests WHERE status = 'pending'")['count'];
$totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'];
$pendingOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'];
$totalApplications = $db->fetchOne("SELECT COUNT(*) as count FROM applications WHERE is_active = 1")['count'];
$openIssues = $db->fetchOne("SELECT COUNT(*) as count FROM issues WHERE status = 'open'")['count'];

jsonResponse([
    'total_users' => $totalUsers,
    'pending_access_requests' => $pendingAccessRequests,
    'total_orders' => $totalOrders,
    'pending_orders' => $pendingOrders,
    'total_applications' => $totalApplications,
    'open_issues' => $openIssues
]);
?>

