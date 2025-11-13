<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT id_vehicule, plaque_immatriculation, hauteur FROM Vehicule');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('SELECT * FROM Vehicule WHERE id_vehicule = ?');
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
        $plaque = $in['plaque_immatriculation'] ?? null;
        $hauteur = isset($in['hauteur']) ? (int) $in['hauteur'] : null;
        $stmt = $pdo->prepare('INSERT INTO Vehicule (plaque_immatriculation, hauteur) VALUES (?, ?)');
        $stmt->execute([$plaque, $hauteur]);
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
        $plaque = $in['plaque_immatriculation'] ?? null;
        $hauteur = isset($in['hauteur']) ? (int) $in['hauteur'] : null;
        $stmt = $pdo->prepare('UPDATE Vehicule SET plaque_immatriculation = ?, hauteur = ? WHERE id_vehicule = ?');
        $stmt->execute([$plaque, $hauteur, $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM Vehicule WHERE id_vehicule = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
