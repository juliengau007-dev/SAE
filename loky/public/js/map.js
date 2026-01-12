// map.js
/**
 * createParkingPopup()
 * Crée le HTML pour la popup personnalisée d'un parking
 */
function createParkingPopup(name, isFree, availableSpots, hasElectric, hasPMR, lat, lon, fid) {
    const tFree = typeof t === "function" ? t("popup_free") : "Gratuit";
    const tPaid = typeof t === "function" ? t("popup_paid") : "Payant";
    const tUnspecified = typeof t === "function" ? t("popup_cost_unspecified") : "Non spécifié";
    const tElectric = typeof t === "function" ? t("popup_electric") : "Électrique";
    const tPMR = typeof t === "function" ? t("popup_pmr") : "PMR";
    const tPlaces = typeof t === "function" ? t("places_label") : "places";
    const tActivateGuidance = typeof t === "function" ? t("popup_activate_guidance") : "Activer le guidage";
    const tAddFavorite = typeof t === "function" ? t("popup_add_favorite") : "Ajouter aux favoris";
    
    const costLabel = isFree === true 
        ? `<span class="parking-popup-badge free">🆓 ${tFree}</span>`
        : isFree === false 
            ? `<span class="parking-popup-badge paid">💳 ${tPaid}</span>`
            : `<span class="parking-popup-badge">❓ ${tUnspecified}</span>`;
    
    const spotsLabel = availableSpots 
        ? `<div class="parking-popup-row">
            <span class="parking-popup-icon">🅿️</span>
            <span><strong>${availableSpots}</strong> ${tPlaces}</span>
          </div>`
        : '';
    
    const features = [];
    if (hasElectric) {
        features.push(`<div class="parking-popup-feature"><span class="parking-popup-feature-icon">⚡</span><span>${tElectric}</span></div>`);
    }
    if (hasPMR) {
        features.push(`<div class="parking-popup-feature"><span class="parking-popup-feature-icon">♿</span><span>${tPMR}</span></div>`);
    }
    
    const featuresHtml = features.length > 0 
        ? `<div class="parking-popup-features">${features.join('')}</div>`
        : '';
    
    return `
        <div class="parking-popup">
            <div class="parking-popup-header">
                <div class="parking-popup-title">${name}</div>
                <button class="parking-popup-favorite" onclick="toggleFavorite(event, ${JSON.stringify(fid)})" title="${tAddFavorite}">
                    ⭐
                </button>
            </div>
            
            <div class="parking-popup-info">
                <div class="parking-popup-row">
                    ${costLabel}
                </div>
                ${spotsLabel}
                ${featuresHtml}
            </div>
            
            <div class="parking-popup-actions">
                <button class="parking-popup-btn-navigate" onclick="goToParking(${lat}, ${lon}, ${JSON.stringify(fid)})">
                    <span>${tActivateGuidance}</span>
                </button>
            </div>
        </div>
    `;
}

/**
 * toggleFavorite()
 * Fonction placeholder pour la gestion des favoris (à implémenter plus tard)
 */
function toggleFavorite(event, fid) {
    event.stopPropagation();
    console.log('Toggle favorite for parking:', fid);
    // TODO: Implémenter la fonctionnalité des favoris
}

/**
 * initApp()
 * Initialise la carte et la géolocalisation, détecte la ville la plus proche
 */
function initApp() {
    if (!navigator.geolocation) {
        alert(
            typeof t === "function"
                ? t("geolocation_unsupported")
                : "La géolocalisation n'est pas supportée par ce navigateur.",
        );
        return;
    }

    navigator.geolocation.getCurrentPosition(async (position) => {
        currentLat = position.coords.latitude;
        currentLon = position.coords.longitude;

        // Détecter la ville la plus proche (nearest.distance est en degrés^2,
        // pas en kilomètres). Convertir en km pour l'affichage.
        const nearestCity = findNearestCityFromPosition(currentLat, currentLon);
        setCurrentCity(nearestCity.key);
        try {
            const km = getDistance(
                currentLat,
                currentLon,
                nearestCity.city.center.lat,
                nearestCity.city.center.lng,
            );
            console.log(
                `Ville détectée: ${nearestCity.city.name} (${
                    km.toFixed(1)
                } km)`,
            );
        } catch (e) {
            console.log(`Ville détectée: ${nearestCity.city.name}`);
        }

        map = L.map("map", { minZoom: 3, maxZoom: 19 }).setView([
            currentLat,
            currentLon,
        ], defaultZoom);

        console.log(currentLat, currentLon);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
            maxZoom: 19,
            noWrap: true,
        }).addTo(map);

        map.setMaxBounds([[-90, -180], [90, 180]]);
        map.on(
            "drag",
            () =>
                map.panInsideBounds([[-90, -180], [90, 180]], {
                    animate: false,
                }),
        );
        map.on("movestart", () => {
            _userInteracting = true;
        });
        map.on("moveend", () => {
            _userInteracting = false;
            _userInteractingTime = Date.now();
        });
        map.on("zoomstart", () => {
            _userInteracting = true;
        });
        map.on("zoomend", () => {
            _userInteracting = false;
            _userInteractingTime = Date.now();
        });

        routingControl = L.Routing.control({
            router: L.Routing.osrmv1({ language: "fr" }),
            waypoints: [],
            routeWhileDragging: true,
            show: true,
            lineOptions: {
                styles: [{ color: "blue", opacity: 0.6, weight: 3 }],
            },
            formatter: new L.Routing.Formatter({
                language:
                    (document.documentElement && document.documentElement.lang)
                        ? document.documentElement.lang
                        : (typeof I18N !== "undefined"
                            ? I18N.currentLang
                            : "fr"),
                units: "metric",
            }),
            createMarker: function (i, wp, nWps) {
                return L.marker(wp.latLng, {
                    opacity: 0,
                    interactive: false,
                    keyboard: false,
                    draggable: false,
                });
            },
        }).addTo(map);
        // traduire si la langue choisis dans les parametres de l'appli est le français (le panneau est en anglais par défaut)
        if (routingControl && routingControl.on && I18N.currentLang === "fr") {
            routingControl.on(
                "routesfound",
                () => setTimeout(translateRoutingInstructions, 50),
            );
            routingControl.on(
                "routeselected",
                () => setTimeout(translateRoutingInstructions, 50),
            );
        }

        // déplacer le panneau de routage dans notre conteneur de guidage
        const routingContainer = document.querySelector(
            ".leaflet-routing-container",
        );
        try {
            document.getElementById("guidage").appendChild(routingContainer);
        } catch (e) {}

        // ajouter un petit bouton pour quitter la navigation
        try {
            const actions = document.createElement("div");
            actions.className = "routing-actions";
            const quitBtn = document.createElement("button");
            quitBtn.className = "small-btn";
            quitBtn.title = typeof t === "function"
                ? t("quit_navigation_title")
                : "Quitter la navigation";
            quitBtn.innerText = "✖";
            quitBtn.onclick = function () {
                quitNavigation();
            };
            actions.appendChild(quitBtn);
            routingContainer.appendChild(actions);
        } catch (e) {
            console.warn("add routing action button failed", e);
        }

        userMarker = L.circleMarker([currentLat, currentLon], {
            radius: 9,
            color: "white",
            weight: 4,
            fillColor: "red",
            fillOpacity: 1,
        }).addTo(map).openPopup();

        // charger paramètres + parkings
        try {
            if (typeof loadMenuSettings === "function") loadMenuSettings();
        } catch (e) {}
        try {
            if (typeof loadCitySettings === "function") loadCitySettings();
        } catch (e) {}
        try {
            await loadParkings();
        } catch (e) {
            console.warn("initial loadParkings failed", e);
        }

        const mt = document.getElementById("availabilityToggle");
        const mu = document.getElementById("cityApiUrl");
        if (mt) {
            mt.addEventListener("change", () => {
                try {
                    if (typeof saveCitySettings === "function") {
                        saveCitySettings();
                    }
                } catch (e) {}
                if (mt.checked && _currentTargetFid != null) {
                    startPolling(_currentTargetFid);
                }
                if (!mt.checked) stopPolling();
                try {
                    findNearestParking();
                } catch (e) {
                    console.warn("refresh nearest failed", e);
                }
                try {
                    loadParkings();
                } catch (e) {
                    console.warn("refresh parkings failed", e);
                }
            });
        }
        if (mu) mu.addEventListener("change", saveCitySettings);

        try {
            await findNearestParking();
        } catch (e) {
            console.warn("initial nearest lookup failed", e);
        }

        // Charger la position maison depuis localStorage
        loadHomePosition();

        watchPositionId = navigator.geolocation.watchPosition(
            updatePosition,
            (err) => {
                console.warn("watchPosition error", err);
            },
            { enableHighAccuracy: true, maximumAge: 500, timeout: 5000 },
        );
    }, (err) => {
        const msg = typeof t === "function"
            ? t("geolocation_error")
            : "Impossible de vous localiser";
        alert(msg + (err && err.message ? (": " + err.message) : "."));
    });
}

// updatePosition: callback watchPosition
function updatePosition(pos) {
    // Ne pas mettre à jour si on est en mode virtuel
    if (virtualModeActive) return;

    currentLat = pos.coords.latitude;
    currentLon = pos.coords.longitude;
    const latlng = [currentLat, currentLon];
    if (userMarker) userMarker.setLatLng(latlng);

    try {
        updateRouting(latlng);
    } catch (e) {
        console.warn("updatePosition routing update", e);
    }
}

// Fonction commune pour mettre à jour le routing (utilisée par updatePosition et moveVirtual)
function updateRouting(latlng) {
    if (!routingControl) return;
    if (!updateRouting._lastRoutingTs) updateRouting._lastRoutingTs = 0;
    const now = Date.now();
    const ROUTING_THROTTLE_MS = 500;
    if (now - updateRouting._lastRoutingTs < ROUTING_THROTTLE_MS) return;
    updateRouting._lastRoutingTs = now;

    const waypoints = routingControl.getWaypoints
        ? routingControl.getWaypoints()
        : null;
    if (
        waypoints && waypoints.length > 1 && waypoints[1] &&
        waypoints[1].latLng
    ) {
        routingControl.setWaypoints([
            L.latLng(latlng[0], latlng[1]),
            waypoints[1].latLng,
        ]);
        try {
            if (
                !_userInteracting &&
                (Date.now() - _userInteractingTime > 3000)
            ) centerWithOffset(L.latLng(latlng[0], latlng[1]));
        } catch (e) {}
    }
}

// loadParkings: charge geojson, applique filtres et ajoute couche
async function loadParkings() {
    try {
        const cityCfg = getCurrentCityConfig();
        const res = await fetch(cityCfg.geojsonEndpoint);
        if (!res.ok) throw new Error("parkings_geojson fetch failed");
        const data = await res.json();
        try {
            lastGeoJson = data;
        } catch (e) {}

        let features = (data.features || []).filter((feature) => {
            try {
                const lat = feature.geometry.coordinates[1];
                const lon = feature.geometry.coordinates[0];
                return getDistance(currentLat, currentLon, lat, lon);
            } catch (e) {
                return false;
            }
        });

        const availabilityEnabled = document.getElementById(
            "availabilityToggle",
        )?.checked;

        if (availabilityEnabled) {
            const idFields = cityCfg.idFields || ["fid", "id"];
            for (const f of features) {
                let fid = null;
                const props = f.properties || {};
                for (const field of idFields) {
                    if (props[field] != null) {
                        fid = props[field];
                        break;
                    }
                }
                if (!fid && f.id && !isNaN(parseInt(f.id))) {
                    fid = parseInt(f.id);
                }
                if (!fid) continue;
                try {
                    const info = await fetchParkingAvailability(fid);
                    if (info) {
                        f.properties = f.properties || {};
                        f.properties._availability = info;
                    }
                } catch (e) {
                    console.warn(
                        `[${cityCfg.name}] availability lookup failed for id`,
                        fid,
                        e,
                    );
                }
            }
        }

        try {
            const heightVal = document.getElementById("heightMax")?.value || "";
            const heightReq = heightVal !== "" ? Number(heightVal) : null;
            const electricOnly =
                document.getElementById("electricToggle")?.checked || false;
            // The UI toggle id is `payantToggle` but we want it to mean "free only":
            // when checked, show only free parkings; when unchecked, show both.
            const freeOnly = document.getElementById("payantToggle")?.checked ||
                false;
            const pmrEnabled = pmr;
            features = features.filter((f) => {
                try {
                    const props = f.properties || {};
                    if (
                        heightReq != null && props.hauteur_max !== undefined &&
                        props.hauteur_max !== null && props.hauteur_max !== ""
                    ) {
                        const pMax = Number(props.hauteur_max);
                        if (!isNaN(pMax) && pMax > 0 && heightReq > pMax) {
                            return false;
                        }
                    }
                    if (electricOnly) {
                        const pElec = props.electrique ||
                            props.electrique_capable ||
                            props.electrique_spots || null;
                        if (!pElec) return false;
                    }
                    if (freeOnly) {
                        // Show only free parkings when toggle is checked.
                        // First, check `props.cout` used by Metz: 'gratuit', 'payant', or null.
                        const coutVal = props.cout ?? props.Cout ?? null;
                        let isFree = null;
                        if (coutVal !== null && coutVal !== undefined) {
                            try {
                                const s = String(coutVal).toLowerCase().trim();
                                if (s === "gratuit") isFree = true;
                                else if (s === "payant") isFree = false;
                                else isFree = null;
                            } catch (e) {
                                isFree = null;
                            }
                        }

                        // If not determined by `cout`, prefer explicit flag `is_free` (1 = free),
                        // otherwise consider absence of cost fields as indication of free.
                        if (isFree === null) {
                            const isFreeVal = props.is_free ?? props.isFree ??
                                null;
                            if (isFreeVal !== null && isFreeVal !== undefined) {
                                isFree = Number(isFreeVal) === 1;
                            } else {
                                const hasCost =
                                    (props.cost_1h || props.cost_2h ||
                                            props.price || props.tarif)
                                        ? true
                                        : false;
                                isFree = !hasCost;
                            }
                        }

                        if (!isFree) return false;
                    }
                    if (pmrEnabled) {
                        const pPmr = props.pmr || props.access_pmr ||
                            props.pmr_accessible || null;
                        if (!pPmr) return false;
                    }
                    if (_currentTargetFid != null) {
                        const fid = props?.fid || props?.id ||
                            (f.id && !isNaN(parseInt(f.id))
                                ? parseInt(f.id)
                                : null);
                        if (
                            fid != null &&
                            Number(fid) === Number(_currentTargetFid)
                        ) return true;
                        return false;
                    }
                    return true;
                } catch (e) {
                    return true;
                }
            });
        } catch (e) {}

        try {
            if (parkingsLayer) {
                map.removeLayer(parkingsLayer);
                parkingsLayer = null;
            }
        } catch (e) {}

        const geo = { type: "FeatureCollection", features: features };
        const idFields = cityCfg.idFields || ["fid", "id"];

        parkingsLayer = L.geoJSON(geo, {
            onEachFeature: (feature, layer) => {
                const nom = feature.properties?.lib || "Parking";
                let fid = null;
                const props = feature.properties || {};
                for (const field of idFields) {
                    if (props[field] != null) {
                        fid = props[field];
                        break;
                    }
                }
                if (!fid && feature.id && !isNaN(parseInt(feature.id))) {
                    fid = parseInt(feature.id);
                }
                fid = fid || 0;

                // Get cost information
                let isFree = null;
                const coutVal = props.cout ?? props.Cout ?? null;
                if (coutVal !== null && coutVal !== undefined) {
                    try {
                        const s = String(coutVal).toLowerCase().trim();
                        if (s === "gratuit") isFree = true;
                        else if (s === "payant") isFree = false;
                    } catch (e) {}
                }
                if (isFree === null) {
                    const isFreeVal = props.is_free ?? props.isFree ?? null;
                    if (isFreeVal !== null && isFreeVal !== undefined) {
                        isFree = Number(isFreeVal) === 1;
                    } else {
                        const hasCost = (props.cost_1h || props.cost_2h || props.price || props.tarif) ? true : false;
                        isFree = !hasCost;
                    }
                }

                // Get available spots
                let availText = "";
                const avail = props._availability;
                if (avail && avail.value != null) {
                    availText = avail.value;
                } else {
                    const knownKeys = cityCfg.availabilityKeys || [
                        "available", "free", "places", "disponible", "nb_places",
                    ];
                    for (const k of knownKeys) {
                        if (props[k] !== undefined && props[k] !== null) {
                            availText = props[k];
                            break;
                        }
                    }
                }

                // Check for electric spots
                const hasElectric = !!(props.electrique || props.electrique_capable || props.electrique_spots);

                // Check for PMR access
                const hasPMR = !!(props.pmr || props.access_xpmr || props.pmr_accessible);

                const popupHtml = createParkingPopup(nom, isFree, availText, hasElectric, hasPMR, 
                    feature.geometry.coordinates[1], feature.geometry.coordinates[0], fid);

                layer.bindPopup(popupHtml, {
                    maxWidth: 320,
                    className: 'custom-parking-popup'
                });
            },
        }).addTo(map);
        // Après le chargement des parkings, mettre à jour le message de guidage
        try {
            if (typeof findNearestParking === "function") {
                await findNearestParking();
            }
        } catch (e) {
            console.warn("post-load nearest lookup failed", e);
        }
    } catch (err) {
        console.error("Erreur lors du chargement des parkings :", err);
    }
}

function goToParking(lat, lon, fid) {
    if (currentLat && currentLon) {
        routingControl.setWaypoints([
            L.latLng(currentLat, currentLon),
            L.latLng(lat, lon),
        ]);
        document.querySelector(".leaflet-routing-container").style.display =
            "flex";
        _currentTargetFid = fid;
        const mg = document.querySelector(".menuGuider");
        if (mg) mg.style.display = "none";
        loadParkings();
        if (
            document.getElementById("availabilityToggle") &&
            document.getElementById("availabilityToggle").checked
        ) startPolling(fid);
    } else {
        alert(
            typeof t === "function"
                ? t("user_position_unknown")
                : "Position de l'utilisateur inconnue.",
        );
    }
}

function quitNavigation() {
    try {
        stopPolling(true);
        if (routingControl) routingControl.setWaypoints([]);
        const routingEl = document.querySelector(".leaflet-routing-container");
        if (routingEl) routingEl.style.display = "none";
        const mg = document.querySelector(".menuGuider");
        if (mg) mg.style.display = "flex";
        loadParkings();
    } catch (e) {
        console.warn("quitNavigation", e);
    }
}

function centrerPosition() {
    if (currentLat && currentLon) {
        map.setView([currentLat, currentLon], 16);
    } else {
        alert(
            typeof t === "function"
                ? t("user_position_unknown")
                : "Position de l'utilisateur inconnue.",
        );
    }
}

async function findNearestParking() {
    try {
        const cityCfg = getCurrentCityConfig();
        const res = await fetch(cityCfg.geojsonEndpoint);
        const data = await res.json();
        const idFields = cityCfg.idFields || ["fid", "id"];

        let candidates = data.features.map((feature) => {
            const lat = feature.geometry.coordinates[1];
            const lon = feature.geometry.coordinates[0];
            const distance = getDistance(currentLat, currentLon, lat, lon);
            return { feature, distance };
        }).filter((c) => !isNaN(c.distance));

        try {
            const heightVal = document.getElementById("heightMax")?.value || "";
            const heightReq = heightVal !== "" ? Number(heightVal) : null;
            const electricOnly =
                document.getElementById("electricToggle")?.checked || false;
            // `payantToggle` acts as "free only" when checked
            const freeOnly = document.getElementById("payantToggle")?.checked ||
                false;

            candidates = candidates.filter((c) => {
                try {
                    const props = c.feature.properties || {};
                    if (
                        heightReq != null && props.hauteur_max !== undefined &&
                        props.hauteur_max !== null && props.hauteur_max !== ""
                    ) {
                        const pMax = Number(props.hauteur_max);
                        if (!isNaN(pMax) && pMax > 0 && heightReq > pMax) {
                            return false;
                        }
                    }
                    if (electricOnly) {
                        const pElec = props.electrique ||
                            props.electrique_capable ||
                            props.electrique_spots || null;
                        if (!pElec) return false;
                    }
                    if (freeOnly) {
                        // Check `props.cout` first (Metz): 'gratuit' | 'payant' | null
                        const coutVal = props.cout ?? props.Cout ?? null;
                        let isFree = null;
                        if (coutVal !== null && coutVal !== undefined) {
                            try {
                                const s = String(coutVal).toLowerCase().trim();
                                if (s === "gratuit") isFree = true;
                                else if (s === "payant") isFree = false;
                                else isFree = null;
                            } catch (e) {
                                isFree = null;
                            }
                        }

                        if (isFree === null) {
                            const isFreeVal = props.is_free ?? props.isFree ??
                                null;
                            if (isFreeVal !== null && isFreeVal !== undefined) {
                                isFree = Number(isFreeVal) === 1;
                            } else {
                                const hasCost =
                                    (props.cost_1h || props.cost_2h ||
                                            props.price || props.tarif)
                                        ? true
                                        : false;
                                isFree = !hasCost;
                            }
                        }

                        if (!isFree) return false;
                    }
                    if (pmr) {
                        const pPmr = props.pmr || props.access_pmr ||
                            props.pmr_accessible || null;
                        if (!pPmr) return false;
                    }
                    return true;
                } catch (e) {
                    return true;
                }
            });

            if (!candidates.length) {
                document.getElementById("nearestParkingName").textContent =
                    typeof t === "function"
                        ? t("nearest_parking_no_match")
                        : "Aucun parking compatible avec ces contraintes";
                return null;
            }
        } catch (e) {
            console.warn("apply param constraints failed", e);
        }

        candidates.sort((a, b) => a.distance - b.distance);

        const availabilityEnabled = document.getElementById(
            "availabilityToggle",
        )?.checked;
        if (!availabilityEnabled) {
            const nearest = candidates.length ? candidates[0].feature : null;
            document.getElementById("nearestParkingName").textContent = nearest
                ? (nearest.properties.lib ||
                    (typeof t === "function" ? t("places") : "Parking inconnu"))
                : (typeof t === "function"
                    ? t("nearest_parking_none")
                    : "Aucun parking trouvé");
            return nearest;
        }

        for (const c of candidates) {
            const feature = c.feature;
            const props = feature.properties || {};
            let fid = null;
            for (const field of idFields) {
                if (props[field] != null) {
                    fid = props[field];
                    break;
                }
            }
            if (!fid && feature.id && !isNaN(parseInt(feature.id))) {
                fid = parseInt(feature.id);
            }
            if (!fid) continue;

            const info = await fetchParkingAvailability(fid);
            if (info && info.value != null) {
                const num = Number(info.value);
                if (!Number.isNaN(num) && num > 0) {
                    document.getElementById("nearestParkingName").textContent =
                        feature.properties.lib || (typeof t === "function"
                            ? t("parking_available")
                            : "Parking disponible");
                    return feature;
                }
            } else continue;
        }

        const cityConfig2 = getCurrentCityConfig();
        document.getElementById("nearestParkingName").textContent =
            typeof t === "function"
                ? t("nearest_parking_unavailable")
                : `Aucun parking disponible (${cityConfig2.name})`;
        return null;
    } catch (err) {
        console.error("Erreur lors du chargement des parkings :", err);
        document.getElementById("nearestParkingName").textContent =
            typeof t === "function"
                ? t("nearest_parking_error")
                : "Erreur de chargement";
    }
}

async function goToNearestParking() {
    try {
        const nearest = await findNearestParking();
        if (!nearest) {
            alert(
                typeof t === "function"
                    ? t("no_parking_found")
                    : "Aucun parking trouvé pour ces critères.",
            );
            return;
        }
        const fid = nearest.properties?.fid || nearest.properties?.id ||
            (nearest.id && !isNaN(parseInt(nearest.id))
                ? parseInt(nearest.id)
                : null);
        goToParking(
            nearest.geometry.coordinates[1],
            nearest.geometry.coordinates[0],
            fid,
        );
    } catch (e) {
        console.warn("goToNearestParking", e);
    }
}

async function attemptAutoSwitchIfNeeded() {
    try {
        const now = Date.now();
        if (now - _lastAutoSwitchTs < AUTO_SWITCH_TTL) return;
        _lastAutoSwitchTs = now;
        if (_currentTargetFid == null) return;
        const nearest = await findNearestParking();
        if (!nearest) return;
        const newFid = nearest.properties?.fid || nearest.properties?.id ||
            (nearest.id && !isNaN(parseInt(nearest.id))
                ? parseInt(nearest.id)
                : null);
        if (!newFid) return;
        if (Number(newFid) === Number(_currentTargetFid)) return;
        goToParking(
            nearest.geometry.coordinates[1],
            nearest.geometry.coordinates[0],
            newFid,
        );
    } catch (e) {
        console.warn("attemptAutoSwitchIfNeeded", e);
    }
}

// ========================================
// VIRTUAL MOVEMENT MODE
// ========================================
let virtualModeActive = false;
let virtualSpeed = 5;
let virtualMoveInterval = null;
let centerClickCount = 0;
let centerClickTimer = null;
const CENTER_CLICK_THRESHOLD = 5;
const CENTER_CLICK_TIMEOUT = 1500; // 1.5 seconds to click 5 times

function initVirtualMoveMode() {
    const btnCentrer = document.getElementById("btnCentrer");
    const virtualMoveMode = document.getElementById("virtualMoveMode");
    const btnExitVirtualMode = document.getElementById("btnExitVirtualMode");
    const virtualSpeedSlider = document.getElementById("virtualSpeedSlider");
    const virtualSpeedValue = document.getElementById("virtualSpeedValue");

    if (!btnCentrer || !virtualMoveMode) return;

    // Handle 5 consecutive clicks on center button
    btnCentrer.addEventListener("click", () => {
        if (virtualModeActive) return;

        centerClickCount++;

        if (centerClickCount === 1) {
            // First click - start timer
            centerClickTimer = setTimeout(() => {
                // Timer expired - perform normal center action
                if (centerClickCount < CENTER_CLICK_THRESHOLD) {
                    centrerPosition();
                }
                centerClickCount = 0;
            }, CENTER_CLICK_TIMEOUT);
        }

        if (centerClickCount >= CENTER_CLICK_THRESHOLD) {
            // 5 clicks reached - activate virtual mode
            clearTimeout(centerClickTimer);
            centerClickCount = 0;
            activateVirtualMode();
        }
    });

    // Exit button
    if (btnExitVirtualMode) {
        btnExitVirtualMode.addEventListener("click", deactivateVirtualMode);
    }

    // Speed slider
    if (virtualSpeedSlider && virtualSpeedValue) {
        virtualSpeedSlider.addEventListener("input", (e) => {
            virtualSpeed = parseInt(e.target.value);
            virtualSpeedValue.textContent = virtualSpeed;
        });
    }

    // D-Pad buttons
    const dpadButtons = document.querySelectorAll(".dpad-btn");
    dpadButtons.forEach((btn) => {
        const direction = btn.dataset.direction;

        // Mouse events
        btn.addEventListener("mousedown", () => startMoving(direction));
        btn.addEventListener("mouseup", stopMoving);
        btn.addEventListener("mouseleave", stopMoving);

        // Touch events for mobile
        btn.addEventListener("touchstart", (e) => {
            e.preventDefault();
            startMoving(direction);
        });
        btn.addEventListener("touchend", stopMoving);
        btn.addEventListener("touchcancel", stopMoving);
    });

    // Home position buttons
    const btnSetHome = document.getElementById("btnSetHome");
    const btnGoHome = document.getElementById("btnGoHome");

    if (btnSetHome) {
        btnSetHome.addEventListener("click", saveHomePosition);
    }
    if (btnGoHome) {
        btnGoHome.addEventListener("click", goToHomePosition);
    }

    // Keyboard support when virtual mode is active
    document.addEventListener("keydown", (e) => {
        if (!virtualModeActive) return;

        switch (e.key) {
            case "ArrowUp":
            case "w":
            case "z":
                e.preventDefault();
                moveVirtual("up");
                break;
            case "ArrowDown":
            case "s":
                e.preventDefault();
                moveVirtual("down");
                break;
            case "ArrowLeft":
            case "a":
            case "q":
                e.preventDefault();
                moveVirtual("left");
                break;
            case "ArrowRight":
            case "d":
                e.preventDefault();
                moveVirtual("right");
                break;
            case "Escape":
                deactivateVirtualMode();
                break;
            case "h":
            case "H":
                // Raccourci clavier pour définir la maison
                saveHomePosition();
                break;
        }
    });
}

function activateVirtualMode() {
    virtualModeActive = true;
    const virtualMoveMode = document.getElementById("virtualMoveMode");
    const menuGuider = document.querySelector(".menuGuider");

    if (virtualMoveMode) {
        virtualMoveMode.style.display = "flex";
    }

    // Hide the guidance menu to avoid overlap
    if (menuGuider) {
        menuGuider.style.display = "none";
    }

    // Show notification
    if (typeof t === "function") {
        console.log(t("virtual_mode_activated"));
    }
}

function deactivateVirtualMode() {
    virtualModeActive = false;
    stopMoving();

    const virtualMoveMode = document.getElementById("virtualMoveMode");
    const menuGuider = document.querySelector(".menuGuider");

    if (virtualMoveMode) {
        virtualMoveMode.style.display = "none";
    }

    // Ne réafficher le menu parking que si aucun guidage n'est en cours
    if (menuGuider && _currentTargetFid == null) {
        menuGuider.style.display = "flex";
    }
}

function startMoving(direction) {
    stopMoving(); // Clear any existing interval
    moveVirtual(direction); // Move immediately
    virtualMoveInterval = setInterval(() => moveVirtual(direction), 100);
}

function stopMoving() {
    if (virtualMoveInterval) {
        clearInterval(virtualMoveInterval);
        virtualMoveInterval = null;
    }
}

function moveVirtual(direction) {
    if (!map || !virtualModeActive) return;

    // Calculate movement based on speed (1-10) and current zoom level
    const zoom = map.getZoom();
    const baseDelta = 0.0001 * virtualSpeed * Math.pow(2, 18 - zoom);

    let latDelta = 0;
    let lonDelta = 0;

    switch (direction) {
        case "up":
            latDelta = baseDelta;
            break;
        case "down":
            latDelta = -baseDelta;
            break;
        case "left":
            lonDelta = -baseDelta;
            break;
        case "right":
            lonDelta = baseDelta;
            break;
    }

    // Update virtual position
    currentLat = (currentLat || 0) + latDelta;
    currentLon = (currentLon || 0) + lonDelta;

    // Clamp to valid coordinates
    currentLat = Math.max(-90, Math.min(90, currentLat));
    currentLon = Math.max(-180, Math.min(180, currentLon));

    // Move the map
    map.setView([currentLat, currentLon], zoom, { animate: false });

    // Update user marker if exists
    if (typeof userMarker !== "undefined" && userMarker) {
        userMarker.setLatLng([currentLat, currentLon]);
    }

    // Mettre à jour l'itinéraire en temps réel
    try {
        updateRouting([currentLat, currentLon]);
    } catch (e) {
        console.warn("moveVirtual routing update", e);
    }

    // Trigger parking reload after movement (debounced)
    if (typeof loadParkings === "function") {
        clearTimeout(window._virtualMoveLoadTimeout);
        window._virtualMoveLoadTimeout = setTimeout(() => {
            loadParkings();
        }, 300);
    }
}

// ========================================
// HOME POSITION MANAGEMENT
// ========================================
function loadHomePosition() {
    try {
        const saved = localStorage.getItem("lokyHomePosition");
        if (saved) {
            homePosition = JSON.parse(saved);
            console.log("Position maison chargée:", homePosition);
        }
    } catch (e) {
        console.warn("loadHomePosition error", e);
    }
}

function saveHomePosition() {
    if (currentLat && currentLon) {
        homePosition = { lat: currentLat, lon: currentLon };
        localStorage.setItem("lokyHomePosition", JSON.stringify(homePosition));
        console.log("Position maison sauvegardée:", homePosition);

        // Afficher une confirmation visuelle
        const msg = typeof t === "function"
            ? t("home_position_saved")
            : "Position maison enregistrée !";
        showTemporaryMessage(msg);
    }
}

function goToHomePosition() {
    if (homePosition && homePosition.lat && homePosition.lon) {
        currentLat = homePosition.lat;
        currentLon = homePosition.lon;

        if (map) {
            map.setView([currentLat, currentLon], 16);
        }
        if (userMarker) {
            userMarker.setLatLng([currentLat, currentLon]);
        }

        // Recharger les parkings pour cette position
        if (typeof loadParkings === "function") {
            loadParkings();
        }

        const msg = typeof t === "function"
            ? t("home_position_go")
            : "Déplacé vers la position maison";
        showTemporaryMessage(msg);
    } else {
        const msg = typeof t === "function"
            ? t("home_position_not_set")
            : "Aucune position maison définie";
        showTemporaryMessage(msg);
    }
}

function showTemporaryMessage(msg) {
    // Créer un toast temporaire
    let toast = document.getElementById("virtualToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "virtualToast";
        toast.className = "virtual-toast";
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.display = "block";
    toast.style.opacity = "1";

    setTimeout(() => {
        toast.style.opacity = "0";
        setTimeout(() => {
            toast.style.display = "none";
        }, 300);
    }, 2000);
}

// startMetzPolling / stopMetzPolling are UI/API related — allow UI to define them if needed
