<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT id_vehicule, fid FROM correspond');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'create':
        $in = json_decode(file_get_contents('php://input'), true);
        $idVeh = isset($in['id_vehicule']) ? (int) $in['id_vehicule'] : 0;
        $fid   = isset($in['fid']) ? (int) $in['fid'] : 0;
        if (!$idVeh || !$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_keys']);
            break;
        }
        $stmt = $pdo->prepare('INSERT INTO correspond (id_vehicule, fid) VALUES (?, ?)');
        $stmt->execute([$idVeh, $fid]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $idVeh = isset($_GET['id_vehicule']) ? (int) $_GET['id_vehicule'] : 0;
        $fid   = isset($_GET['fid']) ? (int) $_GET['fid'] : 0;
        if (!$idVeh || !$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_keys']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM correspond WHERE id_vehicule = ? AND fid = ?');
        $stmt->execute([$idVeh, $fid]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
