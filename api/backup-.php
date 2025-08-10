<?php
// Phase 3 - Database Backup System
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$adminId = Auth::authenticate();

try {
    ensureBackupDirectory();
    
    // Generate backup filename with timestamp
    $timestamp = date('Ymd_His');
    $sourceDb = __DIR__ . '/../database.sqlite';
    $backupFilename = "telegram_bot_backup_{$timestamp}.db";
    $backupPath = BACKUP_DIRECTORY . $backupFilename;
    
    // Create backup by copying the database file
    if (!copy($sourceDb, $backupPath)) {
        throw new Exception("Failed to copy database file");
    }
    
    // Get backup file size
    $backupSize = filesize($backupPath);
    
    // Clean old backups
    cleanOldBackups();
    
    // Log activity
    logActivity($adminId, 'backup', "Created database backup: {$backupFilename}");
    
    jsonResponse([
        'success' => true,
        'message' => 'Backup created successfully',
        'backup_filename' => $backupFilename,
        'backup_path' => $backupPath,
        'backup_size' => $backupSize,
        'created_at' => date('c')
    ]);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Backup creation failed: ' . $e->getMessage()], 500);
}
?>