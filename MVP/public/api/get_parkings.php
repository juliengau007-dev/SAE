<?php
/**
 * Point d'entrée API : retourne le GeoJSON des parkings.
 *
 * Ce fichier délègue le téléchargement du GeoJSON au
 * `ParkingService` (dans `src/Service/ParkingService.php`) puis
 * renvoie le contenu brut. Il agit comme wrapper pour la couche
 * publique (compatibilité avec les anciens chemins).
 */

header('Content-Type: application/json; charset=utf-8');

// Charger le service qui sait récupérer le GeoJSON (WFS distant)
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'ParkingService.php';

use V3\Service\ParkingService;

$svc = new ParkingService();
$data = $svc->getGeoJson();

// En cas d'échec lors de la récupération distante, renvoyer une erreur
if ($data === false) {
    http_response_code(502);
    echo json_encode(["error" => "wfs_unavailable"]);
    exit;
}

// Réponse : GeoJSON tel quel (string)
echo $data;

// EOF
