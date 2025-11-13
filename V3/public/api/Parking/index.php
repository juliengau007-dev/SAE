<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT fid, pmr, electrique, velo, hauteur_max, url, info FROM Parking');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('SELECT * FROM Parking WHERE fid = ?');
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
        $pmr = isset($in['pmr']) ? (int) $in['pmr'] : null;
        $electrique = isset($in['electrique']) ? (int) $in['electrique'] : null;
        $velo = isset($in['velo']) ? (int) $in['velo'] : 0;
        $hauteur_max = isset($in['hauteur_max']) ? (int) $in['hauteur_max'] : null;
        $url = $in['url'] ?? null;
        $info = $in['info'] ?? null;
        $stmt = $pdo->prepare('INSERT INTO Parking (pmr, electrique, velo, hauteur_max, url, info) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$pmr, $electrique, $velo, $hauteur_max, $url, $info]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $in = json_decode(file_get_contents('php://input'), true);
        $pmr = isset($in['pmr']) ? (int) $in['pmr'] : null;
        $electrique = isset($in['electrique']) ? (int) $in['electrique'] : null;
        $velo = isset($in['velo']) ? (int) $in['velo'] : 0;
        $hauteur_max = isset($in['hauteur_max']) ? (int) $in['hauteur_max'] : null;
        $url = $in['url'] ?? null;
        $info = $in['info'] ?? null;
        $stmt = $pdo->prepare('UPDATE Parking SET pmr=?, electrique=?, velo=?, hauteur_max=?, url=?, info=? WHERE fid=?');
        $stmt->execute([$pmr, $electrique, $velo, $hauteur_max, $url, $info, $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM Parking WHERE fid = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
