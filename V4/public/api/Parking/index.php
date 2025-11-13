<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT fid, pmr, electrique, velo, hauteur_max, url, info FROM parking');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('SELECT * FROM parking WHERE fid = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
        } else {
            echo json_encode($row);
        }
        break;

    // ⚠️ Dans ta BDD, fid n’est pas AUTO_INCREMENT.
    // Si tu veux créer des parkings à la main via l’API, il faut fournir un fid.
    case 'create':
        $in = json_decode(file_get_contents('php://input'), true);
        $fid = isset($in['fid']) ? (int) $in['fid'] : 0;
        if (!$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_fid']);
            break;
        }
        $pmr        = isset($in['pmr']) ? (int) $in['pmr'] : null;
        $electrique = isset($in['electrique']) ? (int) $in['electrique'] : null;
        $velo       = isset($in['velo']) ? (int) $in['velo'] : 0;
        $hauteur    = isset($in['hauteur_max']) ? (int) $in['hauteur_max'] : null;
        $url        = $in['url'] ?? null;
        $info       = $in['info'] ?? null;

        $stmt = $pdo->prepare(
            'INSERT INTO parking (fid, pmr, electrique, velo, hauteur_max, url, info) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fid, $pmr, $electrique, $velo, $hauteur, $url, $info]);
        echo json_encode(['ok' => true, 'id' => $fid]);
        break;

    case 'update':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $in = json_decode(file_get_contents('php://input'), true);
        $pmr        = isset($in['pmr']) ? (int) $in['pmr'] : null;
