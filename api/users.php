<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'GET':
        $users = $db->fetchAll("
            SELECT id, telegram_id, username, first_name, last_name, 
                   is_admin, is_approved, registration_date, last_activity 
            FROM users 
            ORDER BY registration_date DESC
        ");
        jsonResponse($users);
        break;
        
    case 'PUT':
        if (!$userId) {
            jsonResponse(['error' => 'User ID required'], 400);
        }
        
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }
        
        if ($action === 'approve') {
            $db->execute("UPDATE users SET is_approved = 1 WHERE id = ?", [$userId]);
            logActivity($adminId, 'update', "Approved user {$user['username']} (ID: {$userId})", 'user', $userId);
            jsonResponse(['message' => 'User approved']);
            
        } elseif ($action === 'admin') {
            $newAdminStatus = $user['is_admin'] ? 0 : 1;
            $db->execute("UPDATE users SET is_admin = ? WHERE id = ?", [$newAdminStatus, $userId]);
            $action_desc = $newAdminStatus ? 'granted' : 'revoked';
            logActivity($adminId, 'update', "Admin privileges {$action_desc} for user {$user['username']} (ID: {$userId})", 'user', $userId);
            jsonResponse(['message' => "User admin status toggled to " . ($newAdminStatus ? 'true' : 'false')]);
            
        } else {
            // Regular user update
            $input = getJsonInput();
            $fields = [];
            $params = [];
            
            if (isset($input['first_name'])) {
                $fields[] = 'first_name = ?';
                $params[] = $input['first_name'];
            }
            if (isset($input['last_name'])) {
                $fields[] = 'last_name = ?';
                $params[] = $input['last_name'];
            }
            if (isset($input['username'])) {
                $fields[] = 'username = ?';
                $params[] = $input['username'];
            }
            
            if (empty($fields)) {
                jsonResponse(['error' => 'No fields to update'], 400);
            }
            
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $db->execute($sql, $params);
            
            logActivity($adminId, 'update', "Updated user {$user['username']} (ID: {$userId})", 'user', $userId);
            jsonResponse(['message' => 'User updated successfully']);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>