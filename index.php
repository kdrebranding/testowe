<?php
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Bot Admin Panel - PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Telegram Bot Admin Panel - PHP</h1>
        <p><strong>Data uruchomienia:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        // Test PHP
        echo '<div class="status success">✅ PHP działa poprawnie! Wersja: ' . PHP_VERSION . '</div>';
        
        // Test SQLite
        try {
            $db = new PDO('sqlite:database.sqlite');
            echo '<div class="status success">✅ Połączenie z bazą SQLite: OK</div>';
            
            // Sprawdź czy tabele istnieją
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            if (count($tables) > 0) {
                echo '<div class="status info">📊 Znalezione tabele: ' . implode(', ', $tables) . '</div>';
            } else {
                echo '<div class="status error">⚠️ Brak tabel w bazie danych. Należy je utworzyć.</div>';
            }
            
        } catch(PDOException $e) {
            echo '<div class="status error">❌ Błąd bazy danych: ' . $e->getMessage() . '</div>';
        }
        
        // Test folderów
        $folders = ['api', 'config', 'includes', 'admin', 'sql'];
        $existing = array_filter($folders, 'is_dir');
        
        if (count($existing) == count($folders)) {
            echo '<div class="status success">✅ Wszystkie foldery utworzone: ' . implode(', ', $existing) . '</div>';
        } else {
            echo '<div class="status error">❌ Brakujące foldery: ' . implode(', ', array_diff($folders, $existing)) . '</div>';
        }
        ?>
        
        <h2>🚀 Następne kroki:</h2>
        <ul>
            <li>✅ Struktura folderów</li>
            <li>⏳ Konfiguracja bazy danych</li>
            <li>⏳ Utworzenie tabel</li>
            <li>⏳ API endpoints</li>
            <li>⏳ Panel administratora</li>
        </ul>
        
        <div style="margin-top: 30px;">
            <a href="admin/" class="btn">🎛️ Panel Admin</a>
            <a href="api/" class="btn">🔌 Test API</a>
        </div>
    </div>
</body>
</html>