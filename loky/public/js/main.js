// main.js - MODIFICATIONS
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
let watchPositionId = null;
let homePosition = null;

// Ville courante détectée
let _currentCity = null;

const geolocPrompt = document.getElementById("geolocPrompt");
const btnAllowGeoloc = document.getElementById("btnAllowGeoloc");
const btnClosePrompt = document.querySelector(".closeBtn");

let geolocEnabled = false; // MODIFIÉ - état de la géolocalisation
const paramToggle = document.getElementById('geolocToggle');

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
    let nearest = {
        key: DEFAULT_CITY,
        city: CITIES[DEFAULT_CITY],
        distance: Infinity,
    };

    for (const [key, city] of Object.entries(CITIES)) {
        const dLat = lat - (city.center.lat || 0);
        const dLng = lng - (city.center.lng || 0);
        const dist = dLat * dLat + dLng * dLng;
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

// Initialisation au chargement du DOM
document.addEventListener("DOMContentLoaded", () => {
    // Afficher la popup de géolocalisation centrée
    if (geolocPrompt) {
        geolocPrompt.style.display = "flex";
    }
    
    // Initialiser le toggle de géolocalisation dans les paramètres
    if (paramToggle) {
        paramToggle.checked = false; // Par défaut désactivé
        paramToggle.addEventListener('change', toggleGeolocation);
    }
    
    // Initialize virtual movement mode
    if (typeof initVirtualMoveMode === "function") {
        initVirtualMoveMode();
    }
});

// Démarre l'app avec géolocalisation
function startAppWithGeoloc() {
    geolocPrompt.style.display = "none";
    geolocEnabled = true;
    
    // Mettre à jour le toggle dans les paramètres
    if (paramToggle) {
        paramToggle.checked = true;
    }
    
    if (typeof initApp === "function") {
        initApp();
    }
    
    // Activer le bouton de guidage
    updateGuidageButtonState();
}

// MODIFIÉ - Démarre l'app sans géolocalisation
function startAppWithoutGeoloc() {
    geolocPrompt.style.display = "none";
    geolocEnabled = false;
    
    // Mettre à jour le toggle dans les paramètres
    if (paramToggle) {
        paramToggle.checked = false;
    }
    
    if (typeof initAppWithoutGeoloc === "function") {
        initAppWithoutGeoloc();
    }
    
    // Désactiver le bouton de guidage
    updateGuidageButtonState();
}

// Bouton Autoriser
btnAllowGeoloc.addEventListener("click", () => {
    startAppWithGeoloc();
});

// Bouton croix pour refuser
btnClosePrompt.addEventListener("click", () => {
    startAppWithoutGeoloc();
});



/**
 * Active ou désactive la géolocalisation depuis le toggle des paramètres
 */
function toggleGeolocation() {
    if (paramToggle.checked) {
        // Activer la géolocalisation
        activateGeolocation();
    } else {
        // Désactiver la géolocalisation
        deactivateGeolocation();
    }
}

/**
 * Active la géolocalisation
 */
function activateGeolocation() {
    if (geolocEnabled) {
        console.log("Géolocalisation déjà active");
        return;
    }
    
    if (!navigator.geolocation) {
        alert(
            typeof t === "function"
                ? t("geolocation_unsupported")
                : "La géolocalisation n'est pas supportée par ce navigateur."
        );
        if (paramToggle) paramToggle.checked = false;
        return;
    }
    
    // Demander la position actuelle
    navigator.geolocation.getCurrentPosition(
        (position) => {
            geolocEnabled = true;
            currentLat = position.coords.latitude;
            currentLon = position.coords.longitude;
            
            console.log("Géolocalisation activée", currentLat, currentLon);
            
            // Si la carte existe, mettre à jour la position
            if (map) {
                map.setView([currentLat, currentLon], map.getZoom() || defaultZoom);
                
                // Mettre à jour ou créer le marqueur utilisateur
                if (userMarker) {
                    userMarker.setLatLng([currentLat, currentLon]);
                } else {
                    userMarker = L.circleMarker([currentLat, currentLon], {
                        radius: 9,
                        color: "white",
                        weight: 4,
                        fillColor: "red",
                        fillOpacity: 1,
                    }).addTo(map);
                }
                
                // Recharger les parkings
                if (typeof loadParkings === "function") {
                    loadParkings();
                }
                if (typeof findNearestParking === "function") {
                    findNearestParking();
                }
            }
            
            // Démarrer le suivi de position
            if (!watchPositionId) {
                watchPositionId = navigator.geolocation.watchPosition(
                    updatePosition,
                    (err) => {
                        console.warn("watchPosition error", err);
                    },
                    { enableHighAccuracy: true, maximumAge: 500, timeout: 5000 }
                );
            }
            
            // Réactiver le menu de guidage
            updateGuidageButtonState();
            
            // Message de confirmation
            if (typeof showTemporaryMessage === "function") {
                showTemporaryMessage(
                    typeof t === "function" 
                        ? t("geolocation_activated") 
                        : "Géolocalisation activée"
                );
            }
        },
        (err) => {
            console.warn("Erreur géolocalisation", err);
            geolocEnabled = false;
            if (paramToggle) paramToggle.checked = false;
            
            alert(
                typeof t === "function"
                    ? t("geolocation_error")
                    : "Impossible d'activer la géolocalisation: " + err.message
            );
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

/**
 * Désactive la géolocalisation
 */
function deactivateGeolocation() {
    if (!geolocEnabled) {
        console.log("Géolocalisation déjà désactivée");
        return;
    }
    
    geolocEnabled = false;
    
    // Arrêter le suivi de position
    if (watchPositionId !== null) {
        navigator.geolocation.clearWatch(watchPositionId);
        watchPositionId = null;
    }
    
    // Désactiver le menu de guidage
    updateGuidageButtonState();
    
    // Ne pas supprimer le marqueur, juste arrêter le suivi
    console.log("Géolocalisation désactivée");
    
    // Message de confirmation
    if (typeof showTemporaryMessage === "function") {
        showTemporaryMessage(
            typeof t === "function" 
                ? t("geolocation_deactivated") 
                : "Géolocalisation désactivée"
        );
    }
}



/**
 * Met à jour l'état du bouton de guidage selon la géolocalisation
 */
function updateGuidageButtonState() {
    const menuGuider = document.querySelector('.menuGuider');
    const btnGuider = document.getElementById('btnGuider');
    const btnGoHomeNav = document.querySelector('.btn-home-nav');
    
    if (!geolocEnabled) {
        // Désactiver le menu de guidage
        if (menuGuider) {
            menuGuider.style.opacity = '0.5';
            menuGuider.style.pointerEvents = 'none';
        }
        if (btnGuider) {
            btnGuider.disabled = true;
            btnGuider.style.cursor = 'not-allowed';
            btnGuider.title = typeof t === "function" 
                ? t("guidage_requires_geoloc") 
                : "Activez la géolocalisation pour utiliser le guidage";
        }
        if (btnGoHomeNav) {
            btnGoHomeNav.disabled = true;
            btnGoHomeNav.style.cursor = 'not-allowed';
            btnGoHomeNav.title = typeof t === "function" 
                ? t("guidage_requires_geoloc") 
                : "Activez la géolocalisation pour utiliser le guidage";
        }
    } else {
        // Réactiver le menu de guidage
        if (menuGuider) {
            menuGuider.style.opacity = '1';
            menuGuider.style.pointerEvents = 'auto';
        }
        if (btnGuider) {
            btnGuider.disabled = false;
            btnGuider.style.cursor = 'pointer';
            btnGuider.title = typeof t === "function" 
                ? t("btn_go") 
                : "Allez";
        }
        if (btnGoHomeNav) {
            btnGoHomeNav.disabled = false;
            btnGoHomeNav.style.cursor = 'pointer';
            btnGoHomeNav.title = typeof t === "function" 
                ? t("btn_go_home") 
                : "Aller à la maison";
        }
    }
}
