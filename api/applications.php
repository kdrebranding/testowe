<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$appId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $applications = $db->fetchAll("
            SELECT * FROM applications 
            ORDER BY created_at DESC
        ");
        jsonResponse($applications);
        break;
        
    case 'POST':
        $input = getJsonInput();
        
        $required = ['name', 'price'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                jsonResponse(['error' => "Field {$field} is required"], 400);
            }
        }
        
        $sql = "INSERT INTO applications (name, description, price, currency, downloader_code, panel_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $input['name'],
            $input['description'] ?? null,
            $input['price'],
            $input['currency'] ?? 'PLN',
            $input['downloader_code'] ?? null,
            $input['panel_url'] ?? null
        ];
        
        $db->execute($sql, $params);
        $newAppId = $db->lastInsertId();
        
        logActivity($adminId, 'create', "Created application {$input['name']} (ID: {$newAppId})", 'application', $newAppId);
        
        $newApp = $db->fetchOne("SELECT * FROM applications WHERE id = ?", [$newAppId]);
        jsonResponse($newApp);
        break;
        
    case 'PUT':
        if (!$appId) {
            jsonResponse(['error' => 'Application ID required'], 400);
        }
        
        $app = $db->fetchOne("SELECT * FROM applications WHERE id = ?", [$appId]);
        if (!$app) {
            jsonResponse(['error' => 'Application not found'], 404);
        }
        
        $input = getJsonInput();
        $fields = [];
        $params = [];
        
        $allowedFields = ['name', 'description', 'price', 'currency', 'downloader_code', 'panel_url', 'is_active'];
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
        $params[] = $appId;
        
        $sql = "UPDATE applications SET " . implode(', ', $fields) . " WHERE id = ?";
        $db->execute($sql, $params);
        
        logActivity($adminId, 'update', "Updated application {$app['name']} (ID: {$appId})", 'application', $appId);
        
        $updatedApp = $db->fetchOne("SELECT * FROM applications WHERE id = ?", [$appId]);
        jsonResponse($updatedApp);
        break;
        
    case 'DELETE':
        if (!$appId) {
            jsonResponse(['error' => 'Application ID required'], 400);
        }
        
        $app = $db->fetchOne("SELECT * FROM applications WHERE id = ?", [$appId]);
        if (!$app) {
            jsonResponse(['error' => 'Application not found'], 404);
        }
        
        // Soft delete by deactivating
        $db->execute("UPDATE applications SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$appId]);
        
        logActivity($adminId, 'delete', "Deactivated application {$app['name']} (ID: {$appId})", 'application', $appId);
        
        jsonResponse(['message' => 'Application deactivated successfully']);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>