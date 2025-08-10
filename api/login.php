<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = getJsonInput();
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(['error' => 'Username and password required'], 400);
}

$ipAddress = getClientIP();
$userAgent = getUserAgent();

$token = Auth::login($username, $password, $ipAddress, $userAgent);

if ($token) {
    jsonResponse([
        'access_token' => $token,
        'token_type' => 'bearer'
    ]);
} else {
    jsonResponse(['error' => 'Invalid credentials'], 401);
}
?>