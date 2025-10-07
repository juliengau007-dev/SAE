<?php
header('Content-Type: application/json');

// URL du WFS GeoJSON
$wfs_url = "https://maps.eurometropolemetz.eu/public/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=public:pub_tsp_sta&srsName=EPSG:4326&outputFormat=application/json&cql_lter=id%20is%20not%20null";

// Récupérer le contenu
$geojson = file_get_contents($wfs_url);

// Retourner le GeoJSON
echo $geojson;
