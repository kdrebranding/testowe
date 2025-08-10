<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$methodId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $methods = $db->fetchAll("
            SELECT * FROM payment_methods 
            ORDER BY created_at DESC
        ");
        
        // Mask sensitive data
        $formattedMethods = array_map(function($method) {
            return [
                'id' => $method['id'],
                'name' => $method['name'],
                'provider' => $method['provider'],
                'api_key' => $method['api_key'] ? '***' . substr($method['api_key'], -4) : null,
                'secret_key' => $method['secret_key'] ? '***' . substr($method['secret_key'], -4) : null,
                'config_data' => $method['config_data'],
                'is_active' => (bool)$method['is_active'],
                'created_at' => $method['created_at'],
                'updated_at' => $method['updated_at']
            ];
        }, $methods);
        
        jsonResponse($formattedMethods);
        break;
        
    case 'POST':
        $input = getJsonInput();
        
        $required = ['name', 'provider'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                jsonResponse(['error' => "Field {$field} is required"], 400);
            }
        }
        
        $sql = "INSERT INTO payment_methods (name, provider, api_key, secret_key, config_data) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $input['name'],
            $input['provider'],
            $input['api_key'] ?? null,
            $input['secret_key'] ?? null,
            $input['config_data'] ?? null
        ];
        
        $db->execute($sql, $params);
        $newMethodId = $db->lastInsertId();
        
        logActivity($adminId, 'create', "Created payment method: {$input['name']} ({$input['provider']}) - ID: {$newMethodId}", 'payment_method', $newMethodId);
        
        // Return created method with masked sensitive data
        $newMethod = $db->fetchOne("SELECT * FROM payment_methods WHERE id = ?", [$newMethodId]);
        jsonResponse([
            'id' => $newMethod['id'],
            'name' => $newMethod['name'],
            'provider' => $newMethod['provider'],
            'api_key' => $newMethod['api_key'] ? '***' . substr($newMethod['api_key'], -4) : null,
            'secret_key' => $newMethod['secret_key'] ? '***' . substr($newMethod['secret_key'], -4) : null,
            'config_data' => $newMethod['config_data'],
            'is_active' => (bool)$newMethod['is_active'],
            'created_at' => $newMethod['created_at'],
            'updated_at' => $newMethod['updated_at']
        ]);
        break;
        
    case 'PUT':
        if (!$methodId) {
            jsonResponse(['error' => 'Payment method ID required'], 400);
        }
        
        $method = $db->fetchOne("SELECT * FROM payment_methods WHERE id = ?", [$methodId]);
        if (!$method) {
            jsonResponse(['error' => 'Payment method not found'], 404);
        }
        
        $input = getJsonInput();
        $fields = [];
        $params = [];
        
        $allowedFields = ['name', 'provider', 'api_key', 'secret_key', 'config_data', 'is_active'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $fields[] = "{$field} = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($fields)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }
        
        $fields[] = 'updated_at = CURRENT_TIMESTAMP';
        $params[] = $methodId;
        
        $sql = "UPDATE payment_methods SET " . implode(', ', $fields) . " WHERE id = ?";
        $db->execute($sql, $params);
        
        logActivity($adminId, 'update', "Updated payment method: {$method['name']} (ID: {$methodId})", 'payment_method', $methodId);
        
        // Return updated method with masked sensitive data
        $updatedMethod = $db->fetchOne("SELECT * FROM payment_methods WHERE id = ?", [$methodId]);
        jsonResponse([
            'id' => $updatedMethod['id'],
            'name' => $updatedMethod['name'],
            'provider' => $updatedMethod['provider'],
            'api_key' => $updatedMethod['api_key'] ? '***' . substr($updatedMethod['api_key'], -4) : null,
            'secret_key' => $updatedMethod['secret_key'] ? '***' . substr($updatedMethod['secret_key'], -4) : null,
            'config_data' => $updatedMethod['config_data'],
            'is_active' => (bool)$updatedMethod['is_active'],
            'created_at' => $updatedMethod['created_at'],
            'updated_at' => $updatedMethod['updated_at']
        ]);
        break;
        
    case 'DELETE':
        if (!$methodId) {
            jsonResponse(['error' => 'Payment method ID required'], 400);
        }
        
        $method = $db->fetchOne("SELECT * FROM payment_methods WHERE id = ?", [$methodId]);
        if (!$method) {
            jsonResponse(['error' => 'Payment method not found'], 404);
        }
        
        // Check if method is used in any payments
        $usageCount = $db->fetchOne("SELECT COUNT(*) as count FROM payments WHERE payment_method = ?", [$method['provider']])['count'];
        
        if ($usageCount > 0) {
            // Soft delete if used
            $db->execute("UPDATE payment_methods SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$methodId]);
            $message = 'Payment method deactivated (was used in payments)';
        } else {
            // Hard delete if not used
            $db->execute("DELETE FROM payment_methods WHERE id = ?", [$methodId]);
            $message = 'Payment method deleted successfully';
        }
        
        logActivity($adminId, 'delete', "Removed payment method: {$method['name']} (ID: {$methodId})", 'payment_method', $methodId);
        
        jsonResponse(['message' => $message]);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>