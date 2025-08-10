<?php
// Phase 3 - Database Backup System
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::authenticate();

try {
    ensureBackupDirectory();
    
    $backups = [];
    $files = glob(BACKUP_DIRECTORY . '*.db');
    
    foreach ($files as $filepath) {
        $filename = basename($filepath);
        $stat = stat($filepath);
        
        $backups[] = [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $stat['size'],
            'created_at' => date('c', $stat['ctime']),
            'modified_at' => date('c', $stat['mtime'])
        ];
    }
    
    // Sort by creation time (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    jsonResponse(['backups' => $backups]);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Failed to list backups: ' . $e->getMessage()], 500);
}
?>