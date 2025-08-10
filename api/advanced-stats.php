<?php
// Phase 3 - Advanced Statistics & Analytics
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::authenticate();
$db = Database::getInstance();

// User registration trends (last 30 days)
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
$userStats = $db->fetchAll("
    SELECT DATE(registration_date) as date, COUNT(*) as count
    FROM users 
    WHERE registration_date >= ?
    GROUP BY DATE(registration_date)
    ORDER BY date
", [$thirtyDaysAgo]);

// Order status distribution
$orderStats = $db->fetchAll("
    SELECT o.status, COUNT(*) as count, 
           COALESCE(SUM(a.price), 0) as total_value
    FROM orders o
    LEFT JOIN applications a ON o.application_id = a.id
    GROUP BY o.status
");

// Application popularity
$appStats = $db->fetchAll("
    SELECT a.name, a.price, COUNT(o.id) as order_count,
           COALESCE(SUM(a.price), 0) as total_revenue
    FROM applications a
    LEFT JOIN orders o ON a.id = o.application_id
    GROUP BY a.id, a.name, a.price
    ORDER BY order_count DESC
");

// Monthly revenue trends (last 6 months)
$sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
$revenueStats = $db->fetchAll("
    SELECT 
        strftime('%Y-%m', o.order_date) as month,
        COALESCE(SUM(a.price), 0) as revenue,
        COUNT(o.id) as order_count
    FROM orders o
    LEFT JOIN applications a ON o.application_id = a.id
    WHERE o.order_date >= ? AND o.status = 'completed'
    GROUP BY strftime('%Y-%m', o.order_date)
    ORDER BY month
", [$sixMonthsAgo]);

// Format response
$response = [
    'user_registration_trends' => array_map(function($row) {
        return [
            'date' => $row['date'],
            'count' => (int)$row['count']
        ];
    }, $userStats),
    
    'order_status_distribution' => array_map(function($row) {
        return [
            'status' => $row['status'],
            'count' => (int)$row['count'],
            'total_value' => (float)$row['total_value']
        ];
    }, $orderStats),
    
    'application_popularity' => array_map(function($row) {
        return [
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'order_count' => (int)$row['order_count'],
            'total_revenue' => (float)$row['total_revenue']
        ];
    }, $appStats),
    
    'monthly_revenue' => array_map(function($row) {
        return [
            'month' => $row['month'],
            'revenue' => (float)$row['revenue'],
            'order_count' => (int)$row['order_count']
        ];
    }, $revenueStats)
];

jsonResponse($response);
?>