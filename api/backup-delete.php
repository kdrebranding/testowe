<?php
// Phase 3 - Database Backup System
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$adminId = Auth::authenticate();
$filename = $_GET['filename'] ?? '';

try {
    // Security check - ensure filename doesn't contain path traversal
    if (!preg_match('/^telegram_bot_backup_\d{8}_\d{6}\.db$/', $filename)) {
        jsonResponse(['error' => 'Invalid filename'], 400);
    }
    
    $backupPath = BACKUP_DIRECTORY . $filename;
    
    if (!file_exists($backupPath)) {
        jsonResponse(['error' => 'Backup file not found'], 404);
    }
    
    if (!unlink($backupPath)) {
        throw new Exception("Failed to delete backup file");
    }
    
    // Log activity
    logActivity($adminId, 'delete', "Deleted database backup: {$filename}");
    
    jsonResponse([
        'success' => true,
        'message' => "Backup {$filename} deleted successfully"
    ]);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Failed to delete backup: ' . $e->getMessage()], 500);
}
?>