<?php
// API endpoint that returns GeoJSON of parkings
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'ParkingService.php';

use Service\ParkingService;

$svc = new ParkingService();
$data = $svc->getGeoJson();

if ($data === false) {
    http_response_code(502);
    echo json_encode(["error" => "Unable to fetch remote WFS data"]);
    exit;
}

echo $data;

// EOF
