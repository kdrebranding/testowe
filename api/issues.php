<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$adminId = Auth::authenticate();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$issueId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        $issues = $db->fetchAll("
            SELECT i.*, u.username, u.first_name, u.last_name
            FROM issues i
            LEFT JOIN users u ON i.user_id = u.id
            ORDER BY i.created_at DESC
        ");
        
        // Format response to match expected structure
        $formattedIssues = array_map(function($issue) {
            return [
                'id' => $issue['id'],
                'user_id' => $issue['user_id'],
                'title' => $issue['title'],
                'description' => $issue['description'],
                'status' => $issue['status'],
                'priority' => $issue['priority'],
                'created_at' => $issue['created_at'],
                'updated_at' => $issue['updated_at'],
                'resolved_at' => $issue['resolved_at'],
                'admin_notes' => $issue['admin_notes'],
                'user' => $issue['username'] ? [
                    'username' => $issue['username'],
                    'first_name' => $issue['first_name'],
                    'last_name' => $issue['last_name']
                ] : null
            ];
        }, $issues);
        
        jsonResponse($formattedIssues);
        break;
        
    case 'POST':
        $input = getJsonInput();
        
        $required = ['user_id', 'title', 'description'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                jsonResponse(['error' => "Field {$field} is required"], 400);
            }
        }
        
        $sql = "INSERT INTO issues (user_id, title, description, priority, admin_notes) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $input['user_id'],
            $input['title'],
            $input['description'],
            $input['priority'] ?? 'medium',
            $input['admin_notes'] ?? null
        ];
        
        $db->execute($sql, $params);
        $newIssueId = $db->lastInsertId();
        
        logActivity($adminId, 'create', "Created issue: {$input['title']} (ID: {$newIssueId})", 'issue', $newIssueId);
        
        // Return created issue with user relation
        $newIssue = $db->fetchOne("
            SELECT i.*, u.username, u.first_name, u.last_name
            FROM issues i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ", [$newIssueId]);
        
        jsonResponse([
            'id' => $newIssue['id'],
            'user_id' => $newIssue['user_id'],
            'title' => $newIssue['title'],
            'description' => $newIssue['description'],
            'status' => $newIssue['status'],
            'priority' => $newIssue['priority'],
            'created_at' => $newIssue['created_at'],
            'updated_at' => $newIssue['updated_at'],
            'resolved_at' => $newIssue['resolved_at'],
            'admin_notes' => $newIssue['admin_notes'],
            'user' => $newIssue['username'] ? [
                'username' => $newIssue['username'],
                'first_name' => $newIssue['first_name'],
                'last_name' => $newIssue['last_name']
            ] : null
        ]);
        break;
        
    case 'PUT':
        if (!$issueId) {
            jsonResponse(['error' => 'Issue ID required'], 400);
        }
        
        $issue = $db->fetchOne("SELECT * FROM issues WHERE id = ?", [$issueId]);
        if (!$issue) {
            jsonResponse(['error' => 'Issue not found'], 404);
        }
        
        $input = getJsonInput();
        $fields = [];
        $params = [];
        
        $allowedFields = ['status', 'priority', 'admin_notes'];
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
        
        // Set resolved_at if status is resolved
        if (isset($input['status']) && $input['status'] === 'resolved') {
            $fields[] = 'resolved_at = CURRENT_TIMESTAMP';
        }
        
        $params[] = $issueId;
        
        $sql = "UPDATE issues SET " . implode(', ', $fields) . " WHERE id = ?";
        $db->execute($sql, $params);
        
        logActivity($adminId, 'update', "Updated issue: {$issue['title']} (ID: {$issueId})", 'issue', $issueId);
        
        // Return updated issue
        $updatedIssue = $db->fetchOne("
            SELECT i.*, u.username, u.first_name, u.last_name
            FROM issues i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ", [$issueId]);
        
        jsonResponse([
            'id' => $updatedIssue['id'],
            'user_id' => $updatedIssue['user_id'],
            'title' => $updatedIssue['title'],
            'description' => $updatedIssue['description'],
            'status' => $updatedIssue['status'],
            'priority' => $updatedIssue['priority'],
            'created_at' => $updatedIssue['created_at'],
            'updated_at' => $updatedIssue['updated_at'],
            'resolved_at' => $updatedIssue['resolved_at'],
            'admin_notes' => $updatedIssue['admin_notes'],
            'user' => $updatedIssue['username'] ? [
                'username' => $updatedIssue['username'],
                'first_name' => $updatedIssue['first_name'],
                'last_name' => $updatedIssue['last_name']
            ] : null
        ]);
        break;
        
    case 'DELETE':
        if (!$issueId) {
            jsonResponse(['error' => 'Issue ID required'], 400);
        }
        
        $issue = $db->fetchOne("SELECT * FROM issues WHERE id = ?", [$issueId]);
        if (!$issue) {
            jsonResponse(['error' => 'Issue not found'], 404);
        }
        
        $db->execute("DELETE FROM issues WHERE id = ?", [$issueId]);
        
        logActivity($adminId, 'delete', "Deleted issue: {$issue['title']} (ID: {$issueId})", 'issue', $issueId);
        
        jsonResponse(['message' => 'Issue deleted successfully']);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}