<?php
namespace Service;

class ParkingServiceStrasbourg
{
    protected $apiUrl;

    public function __construct()
    {
        // API OpenDataSoft pour les parkings de Strasbourg
        $this->apiUrl = 'https://public.opendatasoft.com/api/records/1.0/search/?dataset=mobilityref-france-base-nationale-des-lieux-de-stationnement&facet=nom_commune&refine.com_name=Strasbourg&rows=100';
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
                'header' => "User-Agent: ParkingService/1.0\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $data = @file_get_contents($this->apiUrl, false, $context);
        if ($data === false) {
            return false;
        }

        // Convertir le format OpenDataSoft vers GeoJSON standard
        $json = json_decode($data, true);
        if (!isset($json['records']) || !is_array($json['records'])) {
            return false;
        }

        $features = [];
        foreach ($json['records'] as $record) {
            $fields = $record['fields'] ?? [];
            $geometry = $record['geometry'] ?? null;

            // Si pas de géométrie dans record, construire depuis fields
            if (!$geometry && isset($fields['geo_point_2d'])) {
                $geometry = [
                    'type' => 'Point',
                    'coordinates' => [
                        $fields['xlong'] ?? $fields['geo_point_2d'][1] ?? 0,
                        $fields['ylat'] ?? $fields['geo_point_2d'][0] ?? 0
                    ]
                ];
            }

            if (!$geometry)
                continue;

            $features[] = [
                'type' => 'Feature',
                'id' => $record['recordid'] ?? null,
                'geometry' => $geometry,
                'properties' => [
                    'id' => $fields['id'] ?? $record['recordid'],
                    'id_local' => $fields['id_local'] ?? null,
                    'lib' => $fields['name'] ?? 'Parking',
                    'name' => $fields['name'] ?? 'Parking',
                    'address' => $fields['address'] ?? null,
                    'space_count' => $fields['space_count'] ?? null,
                    'disponible' => $fields['space_count'] ?? null, // capacité totale (pas de dispo temps réel)
                    'hauteur_max' => $fields['max_height'] ?? null,
                    'pmr' => ($fields['disable_count'] ?? 0) > 0,
                    'electrique' => ($fields['electric_car_count'] ?? 0) > 0,
                    'url' => $fields['url'] ?? null,
                    'info' => $fields['info'] ?? null,
                    'is_free' => $fields['is_free'] ?? 0,
                    'cost_1h' => $fields['cost_1h'] ?? null,
                    'cost_2h' => $fields['cost_2h'] ?? null,
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
