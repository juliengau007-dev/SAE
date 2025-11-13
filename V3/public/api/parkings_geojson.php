<?php
// Merge WFS GeoJSON with Parking table attributes from DB
header('Content-Type: application/json; charset=utf-8');

// Use existing WFS endpoint
$wfsPath = __DIR__ . DIRECTORY_SEPARATOR . 'get_parkings.php';
$wfs = @file_get_contents($wfsPath);
if ($wfs === false) {
    http_response_code(502);
    echo json_encode(['error' => 'wfs_unavailable']);
    exit;
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
        $stmt = $pdo->query('SELECT fid, pmr, electrique, velo, hauteur_max, url, info FROM Parking');
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
