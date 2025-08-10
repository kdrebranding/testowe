<?php
header("Content-Type: application/json");

try {
    $parent_dir = dirname(__DIR__);
    require_once $parent_dir . '/config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Debug - sprawdź co jest w tabeli
    $stmt = $db->query("SELECT * FROM applications LIMIT 3");
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "debug" => "test applications",
        "count" => count($apps),
        "data" => $apps
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    echo json_encode([
        "error" => $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
}
?>