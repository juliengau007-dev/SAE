<?php
// public/api/db.php
header('Content-Type: application/json; charset=utf-8');

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
    echo json_encode(['error' => 'db_connect', 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? 'list_users';

if ($action === 'list_users') {
    try {
        $sql = "SELECT 
                    u.id_utilisateur,
                    u.nom,
                    u.email,
                    u.pmr,
                    u.date_creation,
                    MIN(p.id_vehicule) AS id_vehicule
                FROM utilisateur u
                LEFT JOIN possede p ON p.id_utilisateur = u.id_utilisateur
                GROUP BY u.id_utilisateur, u.nom, u.email, u.pmr, u.date_creation";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        echo json_encode(['data' => $rows]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'query_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
