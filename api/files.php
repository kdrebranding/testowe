<?php
// Phase 3 - File Management System
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$fileId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $files = $db->fetchAll("
            SELECT cf.*, u.username, u.first_name, u.last_name 
            FROM client_files cf
            LEFT JOIN users u ON cf.user_id = u.id
            ORDER BY cf.created_at DESC
        ");
        
        // Format response to match expected structure
        $formattedFiles = array_map(function($file) {
            return [
                'id' => $file['id'],
                'user_id' => $file['user_id'],
                'application_name' => $file['application_name'],
                'downloader_code' => $file['downloader_code'],
                'file_url' => $file['file_url'],
                'file_name' => $file['file_name'],
                'file_type' => $file['file_type'],
                'is_active' => (bool)$file['is_active'],
                'created_at' => $file['created_at'],
                'user' => $file['username'] ? [
                    'username' => $file['username'],
                    'first_name' => $file['first_name'],
                    'last_name' => $file['last_name']
                ] : null
            ];
        }, $files);
        
        jsonResponse($formattedFiles);
        break;
        
    case 'POST':
        $input = getJsonInput();
        
        $required = ['user_id', 'file_name'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                jsonResponse(['error' => "Field {$field} is required"], 400);
            }
        }
        
        $sql = "INSERT INTO client_files (user_id, application_name, downloader_code, file_url, file_name, file_type) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $input['user_id'],
            $input['application_name'] ?? null,
            $input['downloader_code'] ?? null,
            $input['file_url'] ?? null,
            $input['file_name'],
            $input['file_type'] ?? 'file'
        ];
        
        $db->execute($sql, $params);
        $newFileId = $db->lastInsertId();
        
        logActivity($adminId, 'create', "Created file {$input['file_name']} for application {$input['application_name']}", 'file', $newFileId);
        
        // Return created file
        $newFile = $db->fetchOne("
            SELECT cf.*, u.username, u.first_name, u.last_name 
            FROM client_files cf
            LEFT JOIN users u ON cf.user_id = u.id
            WHERE cf.id = ?
        ", [$newFileId]);
        
        jsonResponse([
            'id' => $newFile['id'],
            'user_id' => $newFile['user_id'],
            'application_name' => $newFile['application_name'],
            'downloader_code' => $newFile['downloader_code'],
            'file_url' => $newFile['file_url'],
            'file_name' => $newFile['file_name'],
            'file_type' => $newFile['file_type'],
            'is_active' => (bool)$newFile['is_active'],
            'created_at' => $newFile['created_at'],
            'user' => $newFile['username'] ? [
                'username' => $newFile['username'],
                'first_name' => $newFile['first_name'],
                'last_name' => $newFile['last_name']
            ] : null
        ]);
        break;
        
    case 'PUT':
        if (!$fileId) {
            jsonResponse(['error' => 'File ID required'], 400);
        }
        
        $file = $db->fetchOne("SELECT * FROM client_files WHERE id = ?", [$fileId]);
        if (!$file) {
            jsonResponse(['error' => 'File not found'], 404);
        }
        
        $input = getJsonInput();
        $fields = [];
        $params = [];
        
        $allowedFields = ['application_name', 'downloader_code', 'file_url', 'file_name', 'file_type', 'is_active'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $fields[] = "{$field} = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($fields)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }
        
        $params[] = $fileId;
        $sql = "UPDATE client_files SET " . implode(', ', $fields) . " WHERE id = ?";
        $db->execute($sql, $params);
        
        logActivity($adminId, 'update', "Updated file {$file['file_name']} (ID: {$fileId})", 'file', $fileId);
        
        // Return updated file
        $updatedFile = $db->fetchOne("
            SELECT cf.*, u.username, u.first_name, u.last_name 
            FROM client_files cf
            LEFT JOIN users u ON cf.user_id = u.id
            WHERE cf.id = ?
        ", [$fileId]);
        
        jsonResponse([
            'id' => $updatedFile['id'],
            'user_id' => $updatedFile['user_id'],
            'application_name' => $updatedFile['application_name'],
            'downloader_code' => $updatedFile['downloader_code'],
            'file_url' => $updatedFile['file_url'],
            'file_name' => $updatedFile['file_name'],
            'file_type' => $updatedFile['file_type'],
            'is_active' => (bool)$updatedFile['is_active'],
            'created_at' => $updatedFile['created_at'],
            'user' => $updatedFile['username'] ? [
                'username' => $updatedFile['username'],
                'first_name' => $updatedFile['first_name'],
                'last_name' => $updatedFile['last_name']
            ] : null
        ]);
        break;
        
    case 'DELETE':
        if (!$fileId) {
            jsonResponse(['error' => 'File ID required'], 400);
        }
        
        $file = $db->fetchOne("SELECT * FROM client_files WHERE id = ?", [$fileId]);
        if (!$file) {
            jsonResponse(['error' => 'File not found'], 404);
        }
        
        // Soft delete
        $db->execute("UPDATE client_files SET is_active = 0 WHERE id = ?", [$fileId]);
        
        logActivity($adminId, 'delete', "Deactivated file {$file['file_name']} (ID: {$fileId})", 'file', $fileId);
        
        jsonResponse(['message' => 'File deactivated successfully']);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>