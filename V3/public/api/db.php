<?php
// public/api/db.php
// Simple PHP API for local ParkingMetz database (WAMP/phpMyAdmin)
header('Content-Type: application/json; charset=utf-8');

// Basic config - localhost WAMP defaults
$dbHost = '127.0.0.1';
$dbName = 'sae_parking';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_connect', 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? 'list_users';

if ($action === 'list_users') {
    try {
        $stmt = $pdo->query('SELECT id_utilisateur, nom, email, pmr, date_creation, id_vehicule FROM Utilisateur');
        $rows = $stmt->fetchAll();
        echo json_encode(['data' => $rows]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'query_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

// Unknown action
http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
