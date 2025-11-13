<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT id_utilisateur, nom, email, pmr, date_creation, id_vehicule FROM Utilisateur');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('SELECT id_utilisateur, nom, email, pmr, date_creation, id_vehicule FROM Utilisateur WHERE id_utilisateur = ?');
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
        if (!$in || empty($in['email']) || empty($in['mdp'])) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_email_or_mdp']);
            break;
        }
        // Hash password
        $hash = password_hash($in['mdp'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO Utilisateur (nom, email, mdp, pmr, id_vehicule) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$in['nom'] ?? null, $in['email'], $hash, isset($in['pmr']) ? (int) $in['pmr'] : 0, $in['id_vehicule'] ?? null]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'login':
        // Expect JSON body { email, mdp }
        $in = json_decode(file_get_contents('php://input'), true);
        if (!$in || empty($in['email']) || empty($in['mdp'])) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_email_or_mdp']);
            break;
        }
        $stmt = $pdo->prepare('SELECT id_utilisateur, nom, email, mdp, pmr, date_creation, id_vehicule FROM Utilisateur WHERE email = ?');
        $stmt->execute([$in['email']]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_credentials']);
            break;
        }
        $hash = $row['mdp'];
        if (!password_verify($in['mdp'], $hash)) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_credentials']);
            break;
        }
        // remove mdp before returning
        unset($row['mdp']);
        echo json_encode(['ok' => true, 'user' => $row]);
        break;

    case 'update':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $in = json_decode(file_get_contents('php://input'), true);
        $fields = [];
        $params = [];
        if (isset($in['nom'])) {
            $fields[] = 'nom = ?';
            $params[] = $in['nom'];
        }
        if (isset($in['email'])) {
            $fields[] = 'email = ?';
            $params[] = $in['email'];
        }
        if (isset($in['mdp'])) {
            $fields[] = 'mdp = ?';
            $params[] = password_hash($in['mdp'], PASSWORD_DEFAULT);
        }
        if (isset($in['pmr'])) {
            $fields[] = 'pmr = ?';
            $params[] = (int) $in['pmr'];
        }
        if (isset($in['id_vehicule'])) {
            $fields[] = 'id_vehicule = ?';
            $params[] = $in['id_vehicule'];
        }
        if (empty($fields)) {
            echo json_encode(['ok' => true]);
            break;
        }
        $params[] = $id;
        $sql = 'UPDATE Utilisateur SET ' . implode(', ', $fields) . ' WHERE id_utilisateur = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM Utilisateur WHERE id_utilisateur = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
