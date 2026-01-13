<?php
/**
 * Connexion PDO partagée pour les endpoints API.
 *
 * Ce fichier expose la variable `$pdo` (instance de PDO) pour les
 * scripts qui l'incluent. Les identifiants sont définis ici —
 * modifiez-les si vous déployez localement.
 */

// Paramètres de connexion (à adapter selon l'environnement)
$dbHost = 'mysql-siteparkingmetz.alwaysdata.net';
$dbName = 'siteparkingmetz_sae_parking';
$dbUser = '441741_root';
$dbPass = 'mdpPourLeSite';

/* Pour le développement local avec WAMP
$dbHost = '127.0.0.1';
$dbName = 'sae_parking';
$dbUser = 'root';
$dbPass = '';*/

try {
    // Création de l'objet PDO avec options raisonnables
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    // En cas d'échec, renvoyer une erreur JSON et terminer
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'db_connect', 'message' => $e->getMessage()]);
    exit;
}
