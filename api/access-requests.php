<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$requestId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'GET':
        $requests = $db->fetchAll("
            SELECT * FROM access_requests 
            ORDER BY requested_at DESC
        ");
        jsonResponse($requests);
        break;
        
    case 'PUT':
        if (!$requestId) {
            jsonResponse(['error' => 'Request ID required'], 400);
        }
        
        $request = $db->fetchOne("SELECT * FROM access_requests WHERE id = ?", [$requestId]);
        if (!$request) {
            jsonResponse(['error' => 'Access request not found'], 404);
        }
        
        if ($action === 'approve') {
            // Approve request
            $db->execute("
                UPDATE access_requests 
                SET status = 'approved', processed_at = CURRENT_TIMESTAMP, processed_by = ? 
                WHERE id = ?
            ", [$adminId, $requestId]);
            
            // Create user
            $db->execute("
                INSERT INTO users (telegram_id, username, first_name, last_name, is_approved) 
                VALUES (?, ?, ?, ?, 1)
            ", [
                $request['telegram_id'],
                $request['username'],
                $request['first_name'],
                $request['last_name']
            ]);
            
            logActivity($adminId, 'approve', "Approved access request from {$request['username']} and created user account", 'access_request', $requestId);
            
            jsonResponse(['message' => 'Access request approved and user created']);
            
        } elseif ($action === 'reject') {
            $db->execute("
                UPDATE access_requests 
                SET status = 'rejected', processed_at = CURRENT_TIMESTAMP, processed_by = ? 
                WHERE id = ?
            ", [$adminId, $requestId]);
            
            logActivity($adminId, 'reject', "Rejected access request from {$request['username']}", 'access_request', $requestId);
            
            jsonResponse(['message' => 'Access request rejected']);
        } else {
            jsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    case 'DELETE':
        if (!$requestId) {
            jsonResponse(['error' => 'Request ID required'], 400);
        }
        
        $request = $db->fetchOne("SELECT * FROM access_requests WHERE id = ?", [$requestId]);
        if (!$request) {
            jsonResponse(['error' => 'Access request not found'], 404);
        }
        
        $db->execute("DELETE FROM access_requests WHERE id = ?", [$requestId]);
        
        logActivity($adminId, 'delete', "Deleted access request from {$request['username']} (ID: {$requestId})", 'access_request', $requestId);
        
        jsonResponse(['message' => 'Access request deleted successfully']);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>