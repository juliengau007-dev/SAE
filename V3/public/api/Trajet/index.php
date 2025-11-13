<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT id_utilisateur, fid, date_trajet, nb_trajet FROM Trajet');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $uid = isset($_GET['id_utilisateur']) ? (int) $_GET['id_utilisateur'] : 0;
        $fid = isset($_GET['fid']) ? (int) $_GET['fid'] : 0;
        if (!$uid || !$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_keys']);
            break;
        }
        $stmt = $pdo->prepare('SELECT * FROM Trajet WHERE id_utilisateur = ? AND fid = ?');
        $stmt->execute([$uid, $fid]);
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
        $uid = isset($in['id_utilisateur']) ? (int) $in['id_utilisateur'] : 0;
        $fid = isset($in['fid']) ? (int) $in['fid'] : 0;
        $date = $in['date_trajet'] ?? null;
        $nb = isset($in['nb_trajet']) ? (int) $in['nb_trajet'] : 1;
        if (!$uid || !$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_keys']);
            break;
        }
        $stmt = $pdo->prepare('INSERT INTO Trajet (id_utilisateur, fid, date_trajet, nb_trajet) VALUES (?, ?, ?, ?)');
        $stmt->execute([$uid, $fid, $date, $nb]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $uid = isset($_GET['id_utilisateur']) ? (int) $_GET['id_utilisateur'] : 0;
        $fid = isset($_GET['fid']) ? (int) $_GET['fid'] : 0;
        if (!$uid || !$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_keys']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM Trajet WHERE id_utilisateur = ? AND fid = ?');
        $stmt->execute([$uid, $fid]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
