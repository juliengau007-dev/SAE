// map.js
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

        navigator.geolocation.watchPosition(
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
    currentLat = pos.coords.latitude;
    currentLon = pos.coords.longitude;
    const latlng = [currentLat, currentLon];
    if (userMarker) userMarker.setLatLng(latlng);

    try {
        if (!routingControl) return;
        if (!updatePosition._lastRoutingTs) updatePosition._lastRoutingTs = 0;
        const now = Date.now();
        const ROUTING_THROTTLE_MS = 500;
        if (now - updatePosition._lastRoutingTs < ROUTING_THROTTLE_MS) return;
        updatePosition._lastRoutingTs = now;

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
            try {
                const lat1 = currentLat;
                const lon1 = currentLon;
                const lat2 = nearestCity.city.center.lat;
                const lon2 = nearestCity.city.center.lng;
                console.log("DEBUG nearestCity values:", {
                    nearestKey: nearestCity.key,
                    nearestDistance_deg2: nearestCity.distance,
                    lat1,
                    lon1,
                    lat2,
                    lon2,
                });
                const km = calculateDistanceToCity(lat1, lon1, lat2, lon2);
                console.log(
                    `Ville détectée: ${nearestCity.city.name} (${
                        km.toFixed(1)
                    } km)`,
                );
            } catch (e) {
                console.log(`Ville détectée: ${nearestCity.city.name}`);
            }
        }
    } catch (e) {
        console.warn("updatePosition routing update", e);
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

                let availText = "";
                const avail = feature.properties?._availability;
                if (avail) {
                    if (avail.value != null) {
                        availText = (typeof t === "function"
                            ? t("places_label")
                            : "Places") + ": " + avail.value;
                    } else {availText = typeof t === "function"
                            ? t("info_available")
                            : "Info disponible";}
                } else {
                    const p = feature.properties || {};
                    const knownKeys = cityCfg.availabilityKeys || [
                        "available",
                        "free",
                        "places",
                        "disponible",
                        "nb_places",
                    ];
                    for (const k of knownKeys) {
                        if (p[k] !== undefined && p[k] !== null) {
                            availText = (typeof t === "function"
                                ? t("places_label")
                                : "Places") + ": " + p[k];
                            break;
                        }
                    }
                }

                const popupHtml = `
                    <div style="display:flex;flex-direction:column;gap:6px;max-width:240px;">
                        <div style="font-weight:700">${nom}</div>
                        ${
                    availText
                        ? (`<div style="font-size:0.95em;color:#333">${availText}</div>`)
                        : ""
                }
                        <div style="display:flex;justify-content:center;">
                            <button title="${
                    typeof t === "function" ? t("route_title") : "Itinéraire"
                }" style="width:36px;height:32px;border-radius:6px;border:none;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.15);cursor:pointer;font-size:16px;" onclick='goToParking(${
                    feature.geometry.coordinates[1]
                }, ${feature.geometry.coordinates[0]}, ${
                    JSON.stringify(fid)
                })'>➡️</button>
                        </div>
                    </div>
                `;

                layer.bindPopup(popupHtml);
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

// startMetzPolling / stopMetzPolling are UI/API related — allow UI to define them if needed
