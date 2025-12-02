<?php
/**
 * `parkings_geojson.php`
 *
 * Récupère le GeoJSON (WFS distant via `ParkingService`) et fusionne
 * les attributs présents dans la table `parking` de la base de données.
 *
 * Le fichier met en place un cache simple côté serveur (`parkings_cache.json`)
 * pour éviter de redemander le WFS à chaque requête (coût réseau/timeouts).
 */

header('Content-Type: application/json; charset=utf-8');

// Cache côté serveur (durée courte car les parkings peuvent évoluer)
$cacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'parkings_cache.json';
$cacheTtl = 30; // secondes
$wfs = null;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $wfs = @file_get_contents($cacheFile);
}

// Si pas de cache valide, interroger le service local (ParkingService)
if ($wfs === null || $wfs === false) {
    $svcPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'ParkingService.php';
    if (file_exists($svcPath)) {
        require_once $svcPath;
        try {
            $svc = new \Service\ParkingService();
            $wfs = $svc->getGeoJson();
        } catch (Exception $e) {
            $wfs = false;
        }
    } else {
        $wfs = false;
    }

    // Si on a récupéré des données, on met à jour le cache
    if ($wfs !== false && $wfs !== null) {
        @file_put_contents($cacheFile, $wfs);
    }

    // si l'appel au service a échoué, on retombe éventuellement sur un cache
    if (($wfs === false || $wfs === null) && file_exists($cacheFile)) {
        $wfs = @file_get_contents($cacheFile);
    }

    if ($wfs === false || $wfs === null) {
        http_response_code(502);
        echo json_encode(['error' => 'wfs_unavailable']);
        exit;
    }
}

$geo = json_decode($wfs, true);
if (!isset($geo['features']) || !is_array($geo['features'])) {
    // Si le contenu n'est pas attendu, renvoyer brut
    echo $wfs;
    exit;
}

// Si la connexion DB est disponible, charger la table `parking` pour fusion
$dbConnect = __DIR__ . DIRECTORY_SEPARATOR . '_db_connect.php';
$dbMap = [];
if (file_exists($dbConnect)) {
    require $dbConnect; // fournit $pdo
    try {
        $stmt = $pdo->query('SELECT fid, pmr, electrique, velo, hauteur_max, url, info FROM parking');
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $dbMap[(int) $r['fid']] = $r;
        }
    } catch (Exception $e) {
        // en cas d'erreur DB, on continue et on renvoie le WFS d'origine
    }
}

// helper: extraire fid d'une feature (plusieurs heuristiques)
function feature_get_fid($feature)
{
    if (isset($feature['properties']['fid']))
        return (int) $feature['properties']['fid'];
    if (isset($feature['properties']['id']))
        return (int) $feature['properties']['id'];
    if (isset($feature['id'])) {
        if (is_numeric($feature['id']))
            return (int) $feature['id'];
        if (preg_match('/(\d+)$/', $feature['id'], $m))
            return (int) $m[1];
    }
    return null;
}

// Fusionner les attributs DB (si présents) dans chaque feature
foreach ($geo['features'] as &$feature) {
    $fid = feature_get_fid($feature);
    if ($fid !== null && isset($dbMap[$fid])) {
        // Les valeurs DB écrasent les clefs existantes
        $feature['properties'] = array_merge($feature['properties'] ?? [], $dbMap[$fid]);
    }
}

// Renvoyer le GeoJSON enrichi
echo json_encode($geo, JSON_UNESCAPED_UNICODE);
