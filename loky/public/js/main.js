// main.js
// Déclare les variables globales partagées par les autres scripts
let map, userMarker, currentLat, currentLon, routingControl, parkingsLayer;
let pmr = false;
let payant = false;
let _smoothPrev = null;
let _pollInterval = null;
let _pollMs = 5000;
let _currentTargetFid = null;
let _lastAutoSwitchTs = 0;
const AUTO_SWITCH_TTL = 30000;
let lastGeoJson = null;
const _clientCache = {};
const _clientCacheTtl = 10000;
const rayonKm = 50;
const defaultZoom = 16;
let _userInteracting = false;
let _userInteractingTime = 0;
let watchPositionId = null; // ID du watchPosition pour pouvoir l'arrêter
let homePosition = null; // Position "Maison" sauvegardée { lat, lon }

// Ville courante détectée
let _currentCity = null;

/**
 * Calcule la distance entre deux points (Haversine) en km
 */
function calculateDistanceToCity(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

/**
 * Trouve la ville la plus proche de la position utilisateur
 */
function findNearestCityFromPosition(lat, lng) {
    // Comparaison simple : distance euclidienne au carré en degrés
    // (suffisante pour trouver la ville la plus proche, plus rapide)
    let nearest = {
        key: DEFAULT_CITY,
        city: CITIES[DEFAULT_CITY],
        distance: Infinity,
    };

    for (const [key, city] of Object.entries(CITIES)) {
        const dLat = lat - (city.center.lat || 0);
        const dLng = lng - (city.center.lng || 0);
        const dist = dLat * dLat + dLng * dLng; // degrés^2
        // compute approximate km for debug
        let approxKm = null;
        try {
            approxKm = calculateDistanceToCity(
                lat,
                lng,
                city.center.lat,
                city.center.lng,
            );
        } catch (e) {}
        console.debug("nearest-check", { key, dist_deg2: dist, approxKm });
        if (dist < nearest.distance) {
            nearest = { key, city, distance: dist };
        }
    }
    console.debug("nearest-chosen", {
        key: nearest.key,
        distance_deg2: nearest.distance,
    });
    return nearest;
}

/**
 * Obtenir la configuration de la ville courante
 */
function getCurrentCityConfig() {
    return CITIES[_currentCity] || CITIES[DEFAULT_CITY];
}

/**
 * Définir la ville courante
 */
function setCurrentCity(cityKey) {
    if (CITIES[cityKey]) {
        _currentCity = cityKey;
        console.log(`Ville active: ${CITIES[cityKey].name}`);
    }
    updateCityLabel();
    loadParkings();
}

document.addEventListener("DOMContentLoaded", () => {
    if (typeof initApp === "function") {
        initApp();
    } else {
        console.warn("initApp not defined yet — vérifiez l ordre des scripts.");
    }
    // Initialize virtual movement mode
    if (typeof initVirtualMoveMode === "function") {
        initVirtualMoveMode();
    }
});
