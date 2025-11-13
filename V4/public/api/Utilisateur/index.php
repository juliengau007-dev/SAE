<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

// Helper : renvoie la requête de base avec un seul id_vehicule (le premier lié)
function baseUserSelectSql(string $where = '', bool $withPassword = false): string
{
    // si on veut aussi le champ mdp (pour le login)
    $mdpPart = $withPassword ? ', u.mdp' : '';

    $sql = "SELECT 
                u.id_utilisateur,
                u.nom,
                u.email,
                u.pmr,
                u.date_creation
                $mdpPart,
                MIN(p.id_vehicule) AS id_vehicule
            FROM utilisateur u
            LEFT JOIN possede p ON p.id_utilisateur = u.id_utilisateur";

    if ($where) {
        $sql .= " $where";
    }

    $sql .= " GROUP BY u.id_utilisateur, u.nom, u.email, u.pmr, u.date_creation";
    if ($withPassword) {
        $sql .= ", u.mdp";
    }

    return $sql;
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

        $hash  = password_hash($in['mdp'], PASSWORD_DEFAULT);
        $today = date('Y-m-d');

        // insertion dans utilisateur (sans id_vehicule -> géré par possede)
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

        // si on reçoit un id_vehicule, on crée le lien dans possede
        if (!empty($in['id_vehicule'])) {
            $idVeh = (int) $in['id_vehicule'];
            $stmt2 = $pdo->prepare('INSERT IGNORE INTO possede (id_utilisateur, id_vehicule) VALUES (?, ?)');
            $stmt2->execute([$userId, $idVeh]);
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

        // on récupère le hash
        $hash = $row['mdp'];
        if (!password_verify($in['mdp'], $hash)) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid_credentials']);
            break;
        }

        unset($row['mdp']); // on ne renvoie pas le mdp
        echo json_encode(['ok' => true, 'user' => $row]);
        break;

    // -------------------------------------------------
    // UPDATE : modif profil + liaison/déliaison véhicule
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
            echo json_encode(['ok' => true]); // rien à faire
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

            // Update des champs utilisateur
            if (!empty($fields)) {
                $params[] = $id;
                $sql = 'UPDATE utilisateur SET ' . implode(', ', $fields) . ' WHERE id_utilisateur = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            // Gestion de la relation possede via id_vehicule
            if (array_key_exists('id_vehicule', $in)) {
                // on supprime tous les liens existants
                $del = $pdo->prepare('DELETE FROM possede WHERE id_utilisateur = ?');
                $del->execute([$id]);

                // si on a un nouvel id_vehicule non null, on crée le lien
                if (!empty($in['id_vehicule'])) {
                    $idVeh = (int) $in['id_vehicule'];
                    $ins = $pdo->prepare('INSERT INTO possede (id_utilisateur, id_vehicule) VALUES (?, ?)');
                    $ins->execute([$id, $idVeh]);
                }
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
        // grâce aux FK ON DELETE CASCADE, possede / trajet seront nettoyés
        $stmt = $pdo->prepare('DELETE FROM utilisateur WHERE id_utilisateur = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
