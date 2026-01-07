<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    // -------------------------------------------------
    // LISTE DE TOUS LES VÉHICULES
    // -------------------------------------------------
    case 'list':
        $stmt = $pdo->query('
            SELECT v.id_vehicule, v.plaque_immatriculation, v.hauteur,
                   EXISTS(SELECT 1 FROM electrique e WHERE e.id_vehicule = v.id_vehicule) AS electrique,
                   EXISTS(SELECT 1 FROM velo vl WHERE vl.id_vehicule = v.id_vehicule) AS velo
            FROM vehicule v
        ');
        $data = $stmt->fetchAll();
        foreach ($data as &$row) {
            $row['electrique'] = (bool) $row['electrique'];
            $row['velo'] = (bool) $row['velo'];
        }
        echo json_encode(['data' => $data]);
        break;

    // -------------------------------------------------
    // GET : un véhicule par id
    // -------------------------------------------------
    case 'get':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $stmt = $pdo->prepare('
            SELECT v.*, 
                   EXISTS(SELECT 1 FROM electrique e WHERE e.id_vehicule = v.id_vehicule) AS electrique,
                   EXISTS(SELECT 1 FROM velo vl WHERE vl.id_vehicule = v.id_vehicule) AS velo
            FROM vehicule v WHERE v.id_vehicule = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
        } else {
            $row['electrique'] = (bool) $row['electrique'];
            $row['velo'] = (bool) $row['velo'];
            echo json_encode($row);
        }
        break;

    // -------------------------------------------------
    // CREATE : ajouter un véhicule
    // -------------------------------------------------
    case 'create':
        $in = json_decode(file_get_contents('php://input'), true);
        $plaque = $in['plaque_immatriculation'] ?? $in['plaque'] ?? null;
        $hauteur = isset($in['hauteur']) ? (int) $in['hauteur'] : null;
        $electrique = !empty($in['electrique']);
        $velo = !empty($in['velo']);
        $userId = isset($in['id_utilisateur']) ? (int) $in['id_utilisateur'] : 0;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO vehicule (plaque_immatriculation, hauteur) VALUES (?, ?)');
            $stmt->execute([$plaque, $hauteur]);
            $vehicleId = (int) $pdo->lastInsertId();

            // Ajouter dans electrique si nécessaire
            if ($electrique) {
                $pdo->prepare('INSERT INTO electrique (id_vehicule) VALUES (?)')->execute([$vehicleId]);
            }

            // Ajouter dans velo si nécessaire
            if ($velo) {
                $pdo->prepare('INSERT INTO velo (id_vehicule) VALUES (?)')->execute([$vehicleId]);
            }

            // Lier à l'utilisateur si fourni
            if ($userId > 0) {
                $pdo->prepare('INSERT INTO possede (id_utilisateur, id_vehicule) VALUES (?, ?)')->execute([$userId, $vehicleId]);
            }

            $pdo->commit();
            echo json_encode(['ok' => true, 'id' => $vehicleId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'create_failed', 'message' => $e->getMessage()]);
        }
        break;

    // -------------------------------------------------
    // UPDATE : modifier un véhicule
    // -------------------------------------------------
    case 'update':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $in = json_decode(file_get_contents('php://input'), true);
        $plaque = $in['plaque_immatriculation'] ?? $in['plaque'] ?? null;
        $hauteur = isset($in['hauteur']) ? (int) $in['hauteur'] : null;
        $electrique = isset($in['electrique']) ? (bool) $in['electrique'] : null;
        $velo = isset($in['velo']) ? (bool) $in['velo'] : null;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE vehicule SET plaque_immatriculation = ?, hauteur = ? WHERE id_vehicule = ?');
            $stmt->execute([$plaque, $hauteur, $id]);

            // Gestion électrique
            if ($electrique !== null) {
                $pdo->prepare('DELETE FROM electrique WHERE id_vehicule = ?')->execute([$id]);
                if ($electrique) {
                    $pdo->prepare('INSERT INTO electrique (id_vehicule) VALUES (?)')->execute([$id]);
                }
            }

            // Gestion vélo
            if ($velo !== null) {
                $pdo->prepare('DELETE FROM velo WHERE id_vehicule = ?')->execute([$id]);
                if ($velo) {
                    $pdo->prepare('INSERT INTO velo (id_vehicule) VALUES (?)')->execute([$id]);
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
    // DELETE : supprimer un véhicule
    // -------------------------------------------------
    case 'delete':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        // FK CASCADE nettoiera electrique et possede
        $stmt = $pdo->prepare('DELETE FROM vehicule WHERE id_vehicule = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    // -------------------------------------------------
    // USER_VEHICLES : véhicules d'un utilisateur
    // -------------------------------------------------
    case 'user_vehicles':
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_user_id']);
            break;
        }
        $stmt = $pdo->prepare('
            SELECT v.id_vehicule, v.plaque_immatriculation, v.hauteur,
                   EXISTS(SELECT 1 FROM electrique e WHERE e.id_vehicule = v.id_vehicule) AS electrique,
                   EXISTS(SELECT 1 FROM velo vl WHERE vl.id_vehicule = v.id_vehicule) AS velo
            FROM vehicule v
            JOIN possede p ON v.id_vehicule = p.id_vehicule
            WHERE p.id_utilisateur = ?
        ');
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll();
        foreach ($data as &$row) {
            $row['electrique'] = (bool) $row['electrique'];
            $row['velo'] = (bool) $row['velo'];
        }
        echo json_encode(['data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_action']);
}
