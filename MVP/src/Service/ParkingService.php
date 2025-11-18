<?php
namespace V3\Service;

class ParkingService
{
    protected $wfsUrl;

    public function __construct(?string $wfsUrl = null)
    {
        // Default WFS GeoJSON URL (same as original)
        $this->wfsUrl = $wfsUrl ?? 'https://maps.eurometropolemetz.eu/public/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=public:pub_tsp_sta&srsName=EPSG:4326&outputFormat=application/json&cql_lter=id%20is%20not%20null';
    }

    /**
     * Retourne le GeoJSON brut (string) ou false si erreur
     * @return string|false
     */
    public function getGeoJson()
    {
        // Utilise file_get_contents simple; pour production on pourrait utiliser cURL et gestion des timeouts
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: V3-ParkingService/1.0\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $data = @file_get_contents($this->wfsUrl, false, $context);
        if ($data === false) {
            return false;
        }

        return $data;
    }
}

// EOF
