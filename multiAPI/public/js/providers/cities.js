// cities.js - Configuration des villes disponibles

const CITIES = {
    metz: {
        name: "Metz",
        center: { lat: 49.1193, lng: 6.1757 },
        geojsonEndpoint: "api/parkings_geojson.php?city=metz",
        availabilityKeys: [
            "available",
            "free",
            "places",
            "available_places",
            "disponible",
            "nb_places",
            "nombre",
            "places_libres",
            "place_libre",
        ],
        idFields: ["fid", "id"],
    },
    strasbourg: {
        name: "Strasbourg",
        center: { lat: 48.5734, lng: 7.7521 },
        geojsonEndpoint: "api/parkings_geojson.php?city=strasbourg",
        availabilityKeys: [
            "space_count",
            "available",
            "free",
            "places",
            "disponible",
        ],
        idFields: ["id", "id_local", "recordid"],
    },
    london: {
        name: "London",
        center: { lat: 51.5074, lng: -0.1278 },
        geojsonEndpoint: "api/parkings_geojson.php?city=london",
        availabilityKeys: [
            "disponible",
            "space_count",
            "available",
            "free",
        ],
        idFields: ["id", "tfl_id", "naptanId"],
    },
};

const DEFAULT_CITY = "metz";
