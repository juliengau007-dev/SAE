<?php
// Merge WFS GeoJSON with Parking table attributes from DB
header('Content-Type: application/json; charset=utf-8');

// Récupérer la ville demandée (par défaut: metz)
$city = isset($_GET['city']) ? strtolower(trim($_GET['city'])) : 'metz';

// Simple server-side cache to avoid fetching remote WFS on every request
$cacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'parkings_cache_' . $city . '.json';
$genericCacheFile = __DIR__ . DIRECTORY_SEPARATOR . 'parkings_cache.json';
$cacheTtl = 30; // seconds, adjust as needed
$wfs = null;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $wfs = @file_get_contents($cacheFile);
}

if ($wfs === null || $wfs === false) {
    // Charger le service approprié selon la ville
    $basePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR;

    $svc = null;

    switch ($city) {
        case 'strasbourg':
            $svcPath = $basePath . 'ParkingServiceStrasbourg.php';
            if (file_exists($svcPath)) {
                require_once $svcPath;
                try {
                    $svc = new \Service\ParkingServiceStrasbourg();
                } catch (Exception $e) {
                    $svc = null;
                }
            }
            break;

        case 'london':
            $svcPath = $basePath . 'ParkingServiceLondon.php';
            if (file_exists($svcPath)) {
                require_once $svcPath;
                try {
                    $svc = new \Service\ParkingServiceLondon();
                } catch (Exception $e) {
                    $svc = null;
                }
            }
            break;

        case 'metz':
        default:
            $svcPath = $basePath . 'ParkingService.php';
            if (file_exists($svcPath)) {
                require_once $svcPath;
                try {
                    $svc = new \Service\ParkingService();
                } catch (Exception $e) {
                    $svc = null;
                }
            }
            break;
    }

    if ($svc !== null) {
        try {
            $wfs = $svc->getGeoJson();
        } catch (Exception $e) {
            $wfs = false;
        }
    } else {
        $wfs = false;
    }


    // If fetch succeeded, write city-specific cache
    if ($wfs !== false && $wfs !== null) {
        @file_put_contents($cacheFile, $wfs);
    }

    // If fetch failed but city-specific cache exists, fall back to it
    if (($wfs === false || $wfs === null) && file_exists($cacheFile)) {
        $wfs = @file_get_contents($cacheFile);
    }

    // If still no data, try the generic cache file (legacy)
    if (($wfs === false || $wfs === null) && file_exists($genericCacheFile)) {
        $wfs = @file_get_contents($genericCacheFile);
    }

    if ($wfs === false || $wfs === null) {
        http_response_code(502);
        echo json_encode(['error' => 'wfs_unavailable']);
        exit;
    }
}

$geo = json_decode($wfs, true);
if (!isset($geo['features']) || !is_array($geo['features'])) {
    // return original content if unexpected
    echo $wfs;
    exit;
}

// connect to DB via shared connector if available
$dbConnect = __DIR__ . DIRECTORY_SEPARATOR . '_db_connect.php';
$dbMap = [];
if (file_exists($dbConnect)) {
    require $dbConnect; // provides $pdo
    try {
        $stmt = $pdo->query('SELECT fid, pmr, electrique, velo, hauteur_max, url, info FROM parking');
        $rows = $stmt->fetchAll();
        foreach ($rows as $r)
            $dbMap[(int) $r['fid']] = $r;
    } catch (Exception $e) {
        // ignore DB errors, we'll return WFS unmodified
    }
}

// helper to extract fid from feature
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

foreach ($geo['features'] as &$feature) {
    $fid = feature_get_fid($feature);
    if ($fid !== null && isset($dbMap[$fid])) {
        // merge DB attributes into properties, DB values overwrite existing keys
        $feature['properties'] = array_merge($feature['properties'] ?? [], $dbMap[$fid]);
    }
}

echo json_encode($geo, JSON_UNESCAPED_UNICODE);
