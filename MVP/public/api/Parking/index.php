<?php
/**
 * API minimaliste pour gérer les attributs des parkings (table `parking`).
 *
 * Actions supportées (paramètre `action` en GET) :
 * - list : retourne tous les enregistrements
 * - get  : retourne un enregistrement par `id` (GET param `id`)
 * - create : crée un enregistrement via JSON en POST (voir remarque)
 * - update : met à jour un enregistrement (implémentation partielle)
 *
 * NOTE : dans cette base, la colonne `fid` n'est pas en AUTO_INCREMENT.
 * Si vous créez des parkings via l'API, fournissez explicitement `fid`.
 */

require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Retourne un tableau d'objets {fid, pmr, electrique, velo, hauteur_max, url, info}
        $stmt = $pdo->query('SELECT fid, pmr, electrique, velo, hauteur_max, url, info FROM parking');
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        // GET param `id` attendu
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

    case 'create':
        // Lecture du corps JSON envoyé en POST
        $in = json_decode(file_get_contents('php://input'), true);
        $fid = isset($in['fid']) ? (int) $in['fid'] : 0;
        if (!$fid) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_fid']);
            break;
        }
        $pmr = isset($in['pmr']) ? (int) $in['pmr'] : null;
        $electrique = isset($in['electrique']) ? (int) $in['electrique'] : null;
        $velo = isset($in['velo']) ? (int) $in['velo'] : 0;
        $hauteur = isset($in['hauteur_max']) ? (int) $in['hauteur_max'] : null;
        $url = $in['url'] ?? null;
        $info = $in['info'] ?? null;

        $stmt = $pdo->prepare(
            'INSERT INTO parking (fid, pmr, electrique, velo, hauteur_max, url, info) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fid, $pmr, $electrique, $velo, $hauteur, $url, $info]);
        echo json_encode(['ok' => true, 'id' => $fid]);
        break;

    case 'update':
        // Cette action est partiellement implémentée — il faudra
        // valider et filtrer les champs selon vos besoins réels.
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'missing_id']);
            break;
        }
        $in = json_decode(file_get_contents('php://input'), true);
        $pmr = isset($in['pmr']) ? (int) $in['pmr'] : null;
        // ici vous pouvez ajouter le code de mise à jour selon les champs fournis
        http_response_code(501);
        echo json_encode(['error' => 'not_implemented']);
        break;
}