<?php
require_once __DIR__ . '/../_db_connect.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'list';
$userId = $_GET['user_id'] ?? null;

try {
    switch ($action) {
        case 'list':
            // Lister les parkings enregistrés de l'utilisateur
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'user_id required']);
                break;
            }
            $stmt = $pdo->prepare('SELECT * FROM SavedParkings WHERE user_id = ? ORDER BY created_at DESC');
            $stmt->execute([$userId]);
            $parkings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $parkings]);
            break;
            
        case 'get':
            // Récupérer un parking spécifique
            $parkingId = $_GET['parking_id'] ?? null;
            if (!$parkingId) {
                http_response_code(400);
                echo json_encode(['error' => 'parking_id required']);
                break;
            }
            
            $stmt = $pdo->prepare('SELECT * FROM SavedParkings WHERE parking_id = ? LIMIT 1');
            $stmt->execute([$parkingId]);
            $parking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($parking) {
                echo json_encode(['success' => true, 'data' => $parking]);
            } else {
                echo json_encode(['success' => false, 'data' => null]);
            }
            break;
            
        case 'save':
            // Enregistrer ou mettre à jour un parking
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $parkingId = $data['parking_id'] ?? null;
            $parkingName = $data['parking_name'] ?? '';
            $customName = $data['custom_name'] ?? '';
            $note = $data['note'] ?? '';
            $lat = $data['latitude'] ?? null;
            $lon = $data['longitude'] ?? null;
            
            if (!$parkingId || !$customName || $lat === null || $lon === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                break;
            }
            
            // Vérifier si le parking existe déjà pour cet utilisateur
            $stmt = $pdo->prepare('SELECT id_saved FROM SavedParkings WHERE parking_id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$parkingId, $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Mettre à jour
                $stmt = $pdo->prepare('UPDATE SavedParkings SET custom_name = ?, note = ?, updated_at = NOW() WHERE parking_id = ? AND user_id = ?');
                $stmt->execute([$customName, $note, $parkingId, $userId]);
                $id = $existing['id_saved'];
            } else {
                // Insérer
                $stmt = $pdo->prepare('INSERT INTO SavedParkings (user_id, parking_id, parking_name, custom_name, note, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $parkingId, $parkingName, $customName, $note, $lat, $lon]);
                $id = $pdo->lastInsertId();
            }
            
            echo json_encode(['success' => true, 'id' => $id]);
            break;
            
        case 'delete':
            // Supprimer un parking
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $parkingId = $data['parking_id'] ?? null;
            
            if (!$parkingId) {
                http_response_code(400);
                echo json_encode(['error' => 'parking_id required']);
                break;
            }
            
            $stmt = $pdo->prepare('DELETE FROM SavedParkings WHERE parking_id = ? AND user_id = ?');
            $stmt->execute([$parkingId, $userId]);
            
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>
