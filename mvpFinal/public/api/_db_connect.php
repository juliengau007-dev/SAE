<?php
// Shared DB connector for API endpoints
// Adjust credentials if needed
$dbHost = 'mysql-siteparkingmetz.alwaysdata.net';
$dbName = 'siteparkingmetz_sae_parking';
$dbUser = '441741_root';
$dbPass = 'mdpPourLeSite';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'db_connect', 'message' => $e->getMessage()]);
    exit;
}
