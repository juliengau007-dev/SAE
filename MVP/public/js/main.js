// main.js
// Déclare les variables globales partagées par les autres scripts
let map, userMarker, currentLat, currentLon, routingControl, parkingsLayer;
let pmr = false;
let _smoothPrev = null;
let _metzPollInterval = null;
let _metzPollMs = 5000;
let _currentTargetFid = null;
let _lastAutoSwitchTs = 0;
const AUTO_SWITCH_TTL = 30000;
let lastGeoJson = null;
const _metzClientCache = {};
const _metzClientCacheTtl = 10000;
const rayonKm = 50;
const defaultZoom = 16;
let _userInteracting = false;
let _userInteractingTime = 0;

document.addEventListener("DOMContentLoaded", () => {
    if (typeof initApp === "function") {
        initApp();
    } else {
        console.warn("initApp not defined yet — vérifiez l ordre des scripts.");
    }
});
