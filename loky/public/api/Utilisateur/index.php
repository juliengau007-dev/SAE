<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

// Helper : requête SELECT utilisateur de base
function baseUserSelectSql(string $where = '', bool $withPassword = false): string
{
    $mdpPart = $withPassword ? ', u.mdp' : '';
    $sql = "SELECT 
                u.id_utilisateur,
                u.nom,
                u.email,
                u.pmr,
                u.date_creation
                $mdpPart
            FROM utilisateur u";
    if ($where) {
        $sql .= " $where";
    }
    return $sql;
}

// Charge les véhicules liés à un utilisateur
function loadUserVehicles(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('
        SELECT v.*, 
               EXISTS(SELECT 1 FROM electrique e WHERE e.id_vehicule = v.id_vehicule) AS electrique,
               EXISTS(SELECT 1 FROM velo vl WHERE vl.id_vehicule = v.id_vehicule) AS velo
        FROM vehicule v 
        JOIN possede p ON v.id_vehicule = p.id_vehicule 
        WHERE p.id_utilisateur = ?
    ');
    $stmt->execute([$userId]);
    $vehicles = $stmt->fetchAll();
    foreach ($vehicles as &$v) {
        $v['electrique'] = (bool) $v['electrique'];
        $v['velo'] = (bool) $v['velo'];
    }
    return $vehicles;
}

switch ($action) {
    // -------------------------------------------------
    // LISTE DE TOUS LES UTILISATEURS
    // -------------------------------------------------
    case 'list':
        $stmt = $pdo->query(baseUserSelectSql());
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    // -------------------------------------------------
    // GET : un utilisateur par id
    // -------------------------------------------------
    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare(baseUserSelectSql('WHERE u.id_utilisateur = ?'));
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
        } else {
            $row['vehicles'] = loadUserVehicles($pdo, $id);
            echo json_encode($row);
        }
        break;

    // -------------------------------------------------
    // CREATE : inscription
    // -------------------------------------------------
    case 'create':
        $in = json_decode(file_get_contents('php://input'), true);
        if (!$in || empty($in['email']) || empty($in['mdp'])) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_email_or_mdp']);
            break;
        }

        // Vérifier email unique
        $check = $pdo->prepare('SELECT id_utilisateur FROM utilisateur WHERE email = ?');
        $check->execute([$in['email']]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'email_exists']);
            break;
        }

        $hash = password_hash($in['mdp'], PASSWORD_DEFAULT);
        $today = date('Y-m-d');

        $stmt = $pdo->prepare(
            'INSERT INTO utilisateur (nom, email, mdp, pmr, date_creation) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $in['nom'] ?? null,
            $in['email'],
            $hash,
            isset($in['pmr']) ? (int) $in['pmr'] : 0,
            $today
        ]);
        $userId = (int) $pdo->lastInsertId();

        // Créer véhicule si données fournies
        if (!empty($in['vehicle'])) {
            $veh = $in['vehicle'];
            $stmtV = $pdo->prepare('INSERT INTO vehicule (plaque_immatriculation, hauteur) VALUES (?, ?)');
            $stmtV->execute([
                $veh['plaque'] ?? null,
                isset($veh['hauteur']) ? (int) $veh['hauteur'] : null
            ]);
            $vehicleId = (int) $pdo->lastInsertId();

            // Lien possede
            $pdo->prepare('INSERT INTO possede (id_utilisateur, id_vehicule) VALUES (?, ?)')->execute([$userId, $vehicleId]);

            // Electrique ?
            if (!empty($veh['electrique'])) {
                $pdo->prepare('INSERT INTO electrique (id_vehicule) VALUES (?)')->execute([$vehicleId]);
            }
        }

        echo json_encode(['ok' => true, 'id' => $userId]);
        break;

    // -------------------------------------------------
    // LOGIN
    // -------------------------------------------------
    case 'login':
        $in = json_decode(file_get_contents('php://input'), true);
        if (!$in || empty($in['email']) || empty($in['mdp'])) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_email_or_mdp']);
            break;
        }

        $stmt = $pdo->prepare(baseUserSelectSql('WHERE u.email = ?', true));
        $stmt->execute([$in['email']]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_credentials']);
            break;
        }

        if (!password_verify($in['mdp'], $row['mdp'])) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_credentials']);
            break;
        }

        unset($row['mdp']);
        $row['vehicles'] = loadUserVehicles($pdo, $row['id_utilisateur']);
        echo json_encode(['ok' => true, 'user' => $row]);
        break;

    // -------------------------------------------------
    // UPDATE : modif profil + véhicules
    // -------------------------------------------------
    case 'update':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }

        $in = json_decode(file_get_contents('php://input'), true);
        if (!$in) {
            echo json_encode(['ok' => true]);
            break;
        }

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

        try {
            $pdo->beginTransaction();

            if (!empty($fields)) {
                $params[] = $id;
                $sql = 'UPDATE utilisateur SET ' . implode(', ', $fields) . ' WHERE id_utilisateur = ?';
                $pdo->prepare($sql)->execute($params);
            }

            $pdo->commit();
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'update_failed', 'message' => $e->getMessage()]);
        }
        break;

    // -------------------------------------------------
    // DELETE
    // -------------------------------------------------
    case 'delete':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('DELETE FROM utilisateur WHERE id_utilisateur = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
