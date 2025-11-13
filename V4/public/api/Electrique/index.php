<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT id_vehicule FROM electrique');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('SELECT * FROM electrique WHERE id_vehicule = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
        } else {
            echo json_encode($row);
        }
        break;

    case 'create':
        $in = json_decode(file_get_contents('php://input'), true);
        $idVeh = isset($in['id_vehicule']) ? (int) $in['id_vehicule'] : 0;
        if (!$idVeh) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id_vehicule']);
            break;
        }
        $stmt = $pdo->prepare('INSERT INTO electrique (id_vehicule) VALUES (?)');
        $stmt->execute([$idVeh]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $idVeh = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$idVeh) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM electrique WHERE id_vehicule = ?');
        $stmt->execute([$idVeh]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
