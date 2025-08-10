<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$orderId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $orders = $db->fetchAll("
            SELECT o.*, u.username, u.first_name, u.last_name,
                   a.name as application_name, a.price as application_price
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN applications a ON o.application_id = a.id
            ORDER BY o.order_date DESC
        ");
        
        // Format response to match expected structure
        $formattedOrders = array_map(function($order) {
            return [
                'id' => $order['id'],
                'user_id' => $order['user_id'],
                'application_id' => $order['application_id'],
                'logo_file_id' => $order['logo_file_id'],
                'logo_filename' => $order['logo_filename'],
                'status' => $order['status'],
                'order_date' => $order['order_date'],
                'completion_date' => $order['completion_date'],
                'notes' => $order['notes'],
                'user' => $order['username'] ? [
                    'username' => $order['username'],
                    'first_name' => $order['first_name'],
                    'last_name' => $order['last_name']
                ] : null,
                'application' => $order['application_name'] ? [
                    'name' => $order['application_name'],
                    'price' => (float)$order['application_price']
                ] : null
            ];
        }, $orders);
        
        jsonResponse($formattedOrders);
        break;
        
    case 'POST':
        $input = getJsonInput();
        
        $required = ['user_id', 'application_id'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                jsonResponse(['error' => "Field {$field} is required"], 400);
            }
        }
        
        $sql = "INSERT INTO orders (user_id, application_id, logo_file_id, logo_filename, notes) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $input['user_id'],
            $input['application_id'],
            $input['logo_file_id'] ?? null,
            $input['logo_filename'] ?? null,
            $input['notes'] ?? null
        ];
        
        $db->execute($sql, $params);
        $newOrderId = $db->lastInsertId();
        
        // Get application name for logging
        $app = $db->fetchOne("SELECT name FROM applications WHERE id = ?", [$input['application_id']]);
        $appName = $app['name'] ?? 'Unknown';
        
        logActivity($adminId, 'create', "Created order for application {$appName} (Order ID: {$newOrderId})", 'order', $newOrderId);
        
        // Return created order with relations
        $newOrder = $db->fetchOne("
            SELECT o.*, u.username, u.first_name, u.last_name,
                   a.name as application_name, a.price as application_price
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN applications a ON o.application_id = a.id
            WHERE o.id = ?
        ", [$newOrderId]);
        
        jsonResponse([
            'id' => $newOrder['id'],
            'user_id' => $newOrder['user_id'],
            'application_id' => $newOrder['application_id'],
            'logo_file_id' => $newOrder['logo_file_id'],
            'logo_filename' => $newOrder['logo_filename'],
            'status' => $newOrder['status'],
            'order_date' => $newOrder['order_date'],
            'completion_date' => $newOrder['completion_date'],
            'notes' => $newOrder['notes'],
            'user' => $newOrder['username'] ? [
                'username' => $newOrder['username'],
                'first_name' => $newOrder['first_name'],
                'last_name' => $newOrder['last_name']
            ] : null,
            'application' => $newOrder['application_name'] ? [
                'name' => $newOrder['application_name'],
                'price' => (float)$newOrder['application_price']
            ] : null
        ]);
        break;
        
    case 'PUT':
        if (!$orderId) {
            jsonResponse(['error' => 'Order ID required'], 400);
        }
        
        $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            jsonResponse(['error' => 'Order not found'], 404);
        }
        
        $input = getJsonInput();
        
        if (isset($input['status'])) {
            $sql = "UPDATE orders SET status = ?";
            $params = [$input['status']];
            
            if ($input['status'] === 'completed') {
                $sql .= ", completion_date = CURRENT_TIMESTAMP";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $orderId;
            
            $db->execute($sql, $params);
            
            logActivity($adminId, 'update', "Updated order status to {$input['status']} (Order ID: {$orderId})", 'order', $orderId);
            
            jsonResponse(['message' => "Order status updated to {$input['status']}"]);
        } else {
            jsonResponse(['error' => 'Status field required'], 400);
        }
        break;
        
    case 'DELETE':
        if (!$orderId) {
            jsonResponse(['error' => 'Order ID required'], 400);
        }
        
        $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            jsonResponse(['error' => 'Order not found'], 404);
        }
        
        $db->execute("DELETE FROM orders WHERE id = ?", [$orderId]);
        
        logActivity($adminId, 'delete', "Deleted order (ID: {$orderId})", 'order', $orderId);
        
        jsonResponse(['message' => 'Order deleted successfully']);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>