<?php
require_once 'config/database.php';

// Sprawdź czy formularz został wysłany
if (isset($_POST['action']) && $_POST['action'] == 'install') {
    try {
        $database = new Database();
        $database->createTables();
        $success = "✅ Baza danych została pomyślnie utworzona!";
    } catch (Exception $e) {
        $error = "❌ Błąd: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalator Bazy Danych</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #bee5eb; }
        .btn { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #218838; }
        .btn-back { background: #007bff; }
        .btn-back:hover { background: #0056b3; }
        .btn-test { background: #17a2b8; }
        .btn-test:hover { background: #138496; }
        ul { list-style-type: none; padding-left: 0; }
        ul li { padding: 5px 0; }
        ul li:before { content: "✅ "; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Instalator Bazy Danych</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            
            <div class="info">
                <h3>📊 Utworzone tabele:</h3>
                <ul>
                    <li>users - Użytkownicy</li>
                    <li>applications - Aplikacje</li> 
                    <li>orders - Zamówienia</li>
                    <li>payments - Płatności</li>
                    <li>issues - Zgłoszenia</li>
                    <li>client_files - Pliki</li>
                    <li>admin_messages - Wiadomości</li>
                    <li>access_requests - Żądania dostępu</li>
                    <li>user_sessions - Sesje użytkowników</li>
                </ul>
            </div>
            
            <div class="info">
                <h3>🎯 Dodane testowe dane:</h3>
                <ul>
                    <li>Admin user (telegram_id: 123456789)</li>
                    <li>3 przykładowych użytkowników</li>
                    <li>3 przykładowe aplikacje</li>
                    <li>2 żądania dostępu</li>
                </ul>
            </div>
            
            <div class="info">
                <h3>🔑 Dane logowania:</h3>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
            
            <p><strong>🚀 Następne kroki:</strong></p>
            <a href="index.php" class="btn btn-back">🏠 Strona główna</a>
            <a href="api/" class="btn btn-test">🔌 Test API</a>
            <a href="admin/" class="btn">🎛️ Panel Admin</a>
            
        <?php elseif (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
            <p>Sprawdź uprawnienia do plików i spróbuj ponownie.</p>
            <a href="install.php" class="btn">🔄 Spróbuj ponownie</a>
            <a href="index.php" class="btn btn-back">🏠 Powrót</a>
            
        <?php else: ?>
            <p>Ten instalator utworzy wszystkie tabele w bazie danych i doda przykładowe dane testowe zgodnie z oryginalną strukturą SQL.</p>
            
            <div class="info">
                <h3>📋 Co zostanie utworzone:</h3>
                <ul>
                    <li>9 tabel zgodnie z oryginalną strukturą SQL</li>
                    <li>Indeksy dla optymalizacji wydajności</li>
                    <li>Przykładowe dane testowe</li>
                    <li>Konto administratora (admin/admin123)</li>
                    <li>Relacje między tabelami (FOREIGN KEY)</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="install">
                    <button type="submit" class="btn">🚀 Zainstaluj bazę danych</button>
                </form>
            </div>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="index.php" class="btn btn-back">🏠 Powrót</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>