<?php
namespace Service;

class ParkingServiceLondon
{
    protected $apiUrl;

    public function __construct()
    {
        // API TfL pour les parkings de Londres
        $this->apiUrl = 'https://api.tfl.gov.uk/Place/Type/CarPark';
    }

    /**
     * Retourne le GeoJSON brut (string) ou false si erreur
     * @return string|false
     */
    public function getGeoJson()
    {
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => "User-Agent: ParkingService/1.0\r\nAccept: application/json\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $data = @file_get_contents($this->apiUrl, false, $context);
        if ($data === false) {
            return false;
        }

        // Convertir le format TfL vers GeoJSON standard
        $json = json_decode($data, true);
        if (!is_array($json)) {
            return false;
        }

        $features = [];
        foreach ($json as $place) {
            // Coordonnées
            $lat = $place['lat'] ?? null;
            $lon = $place['lon'] ?? null;

            if ($lat === null || $lon === null) {
                continue;
            }

            $geometry = [
                'type' => 'Point',
                'coordinates' => [(float) $lon, (float) $lat]
            ];

            // Extraire les propriétés additionnelles
            $additionalProps = [];
            if (isset($place['additionalProperties']) && is_array($place['additionalProperties'])) {
                foreach ($place['additionalProperties'] as $prop) {
                    $key = $prop['key'] ?? null;
                    $value = $prop['value'] ?? null;
                    if ($key !== null) {
                        $additionalProps[$key] = $value;
                    }
                }
            }

            // Calculer les places disponibles
            $capacity = isset($additionalProps['Capacity']) ? (int) $additionalProps['Capacity'] : null;
            $occupied = isset($additionalProps['Occupancy']) ? (int) $additionalProps['Occupancy'] : null;
            $disponible = null;
            if ($capacity !== null && $occupied !== null) {
                $disponible = $capacity - $occupied;
            }

            // Détecter si le parking est ouvert (TfL AdditionalProperties key 'Open')
            $available = null;
            if (isset($additionalProps['Open'])) {
                $v = strtolower(trim((string) $additionalProps['Open']));
                if ($v === 'true' || $v === '1' || $v === 'open' || $v === 'yes') {
                    $available = true;
                } elseif ($v === 'false' || $v === '0' || $v === 'closed' || $v === 'no') {
                    $available = false;
                }
            }

            // Extraire PMR, hauteur (m -> cm) et borne électrique depuis additionalProps
            $pmr = false;
            if (isset($additionalProps['NumberOfDisabledBays'])) {
                $pmr = ((int) $additionalProps['NumberOfDisabledBays']) > 0;
            }

            $hauteur_cm = null;
            if (isset($additionalProps['MaxHeightMetres'])) {
                $h = str_replace(',', '.', (string) $additionalProps['MaxHeightMetres']);
                if (is_numeric($h)) {
                    $hauteur_cm = (int) round(floatval($h) * 100);
                }
            }

            $electrique = null;
            if (isset($additionalProps['CarElectricalChargingPoints'])) {
                $v = strtolower(trim((string) $additionalProps['CarElectricalChargingPoints']));
                $electrique = in_array($v, ['1', 'true', 'yes'], true);
            }

            $features[] = [
                'type' => 'Feature',
                'id' => $place['id'] ?? null,
                'geometry' => $geometry,
                'properties' => [
                    'id' => $place['id'] ?? null,
                    'lib' => $place['commonName'] ?? 'Car Park',
                    'name' => $place['commonName'] ?? 'Car Park',
                    'address' => $place['placeType'] ?? null,
                    'space_count' => $capacity,
                    'disponible' => $disponible,
                    'occupancy' => $occupied,
                    'hauteur_max' => $hauteur_cm,
                    'pmr' => $pmr,
                    'electrique' => $electrique,
                    'cout' => 'payant',
                    'url' => $place['url'] ?? null,
                    'info' => $additionalProps['Notes'] ?? null,
                    'available' => $available,
                    'is_free' => false,
                    'cost_1h' => null,
                    'cost_2h' => null,
                    // Propriétés spécifiques Londres
                    'tfl_id' => $place['id'] ?? null,
                    'naptanId' => $place['naptanId'] ?? null,
                ]
            ];
        }

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features
        ];

        return json_encode($geoJson, JSON_UNESCAPED_UNICODE);
    }
}
