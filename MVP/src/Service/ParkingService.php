<?php
namespace Service;

/**
 * Service responsable de la récupération du GeoJSON distant (WFS).
 *
 * Cette classe encapsule l'URL WFS et expose une méthode simple
 * `getGeoJson()` qui retourne le GeoJSON brut (string) ou `false`
 * en cas d'erreur.
 */
class ParkingService
{
    protected $wfsUrl;

    public function __construct(?string $wfsUrl = null)
    {
        // URL WFS par défaut (peut être surchargée pour les tests)
        $this->wfsUrl = $wfsUrl ?? 'https://maps.eurometropolemetz.eu/public/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=public:pub_tsp_sta&srsName=EPSG:4326&outputFormat=application/json&cql_lter=id%20is%20not%20null';
    }

    /**
     * Retourne le GeoJSON brut (string) ou false si erreur.
     *
     * Remarque : on utilise ici `file_get_contents` avec un timeout court
     * pour la simplicité. En production, préférez cURL avec gestion fine
     * des erreurs et des timeouts.
     *
     * @return string|false
     */
    public function getGeoJson()
    {
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
            // En cas d'échec réseau, retourner false pour que l'appelant
            // puisse gérer la dégradation (ex: utiliser un cache).
            return false;
        }

        return $data;
    }
}

// EOF
