// map.js
/**
 * initApp()
 * Initialise la carte et la géolocalisation, crée routingControl, etc.
 */
function initApp() {
    if (!navigator.geolocation) {
        alert("La géolocalisation n'est pas supportée par ce navigateur.");
        return;
    }

    navigator.geolocation.getCurrentPosition(async (position) => {
        currentLat = position.coords.latitude;
        currentLon = position.coords.longitude;

        map = L.map("map", { minZoom: 3, maxZoom: 19 }).setView([
            currentLat,
            currentLon,
        ], defaultZoom);

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
                language: "fr",
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

        if (routingControl && routingControl.on) {
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
            quitBtn.title = "Quitter la navigation";
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
            if (typeof loadMetzSettings === "function") loadMetzSettings();
        } catch (e) {}
        try {
            await loadParkings();
        } catch (e) {
            console.warn("initial loadParkings failed", e);
        }

        const mt = document.getElementById("metzToggle");
        const mu = document.getElementById("metzApiUrl");
        if (mt) {
            mt.addEventListener("change", () => {
                try {
                    if (
                        typeof saveMetzSettings === "function"
                    ) saveMetzSettings();
                } catch (e) {}
                if (mt.checked && _currentTargetFid != null) {
                    startMetzPolling(_currentTargetFid);
                }
                if (!mt.checked) stopMetzPolling();
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
        if (mu) mu.addEventListener("change", saveMetzSettings);

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
        alert("Impossible de vous localiser : " + err.message);
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
        }
    } catch (e) {
        console.warn("updatePosition routing update", e);
    }
}

// loadParkings: charge geojson, applique filtres et ajoute couche
async function loadParkings() {
    try {
        const res = await fetch("api/parkings_geojson.php");
        if (!res.ok) throw new Error("parkings_geojson fetch failed");
        const data = await res.json();
        try {
            lastGeoJson = data;
        } catch (e) {}

        let features = (data.features || []).filter((feature) => {
            try {
                const lat = feature.geometry.coordinates[1];
                const lon = feature.geometry.coordinates[0];
                return getDistance(currentLat, currentLon, lat, lon) <= rayonKm;
            } catch (e) {
                return false;
            }
        });

        const metzEnabled = document.getElementById("metzToggle")?.checked;

        if (metzEnabled) {
            const kept = [];
            for (const f of features) {
                const fid = f.properties?.fid || f.properties?.id ||
                    (f.id && !isNaN(parseInt(f.id)) ? parseInt(f.id) : null);
                if (!fid) continue;
                try {
                    const info = await fetchMetzAvailabilityOnce(fid);
                    if (info) {
                        f.properties = f.properties || {};
                        f.properties._metz_availability = info;
                        kept.push(f);
                    }
                } catch (e) {
                    console.warn("metz lookup failed for fid", fid, e);
                }
            }
            features = kept;
        }

        try {
            const heightVal = document.getElementById("heightMax")?.value || "";
            const heightReq = heightVal !== "" ? Number(heightVal) : null;
            const electricOnly =
                document.getElementById("electricToggle")?.checked || false;
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
        parkingsLayer = L.geoJSON(geo, {
            onEachFeature: (feature, layer) => {
                const nom = feature.properties?.lib || "Parking";
                const fid = feature.properties?.fid || feature.properties?.id ||
                    (feature.id && !isNaN(parseInt(feature.id))
                        ? parseInt(feature.id)
                        : 0);
                let availText = "";
                const metz = feature.properties?._metz_availability;
                if (metz) {
                    if (metz.value != null) {
                        availText = "Places: " + metz.value;
                    } else availText = "Info disponible";
                } else {
                    const p = feature.properties || {};
                    const knownKeys = [
                        "available",
                        "free",
                        "places",
                        "available_places",
                        "disponible",
                        "nb_places",
                        "places_libres",
                        "place_libre",
                    ];
                    for (const k of knownKeys) {
                        if (p[k] !== undefined && p[k] !== null) {
                            availText = "Places: " + p[k];
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
                            <button title="Itinéraire" style="width:36px;height:32px;border-radius:6px;border:none;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.15);cursor:pointer;font-size:16px;" onclick="goToParking(${
                    feature.geometry.coordinates[1]
                }, ${feature.geometry.coordinates[0]}, ${fid})">➡️</button>
                        </div>
                    </div>
                `;

                layer.bindPopup(popupHtml);
            },
        }).addTo(map);
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
            document.getElementById("metzToggle") &&
            document.getElementById("metzToggle").checked
        ) startMetzPolling(fid);
    } else {
        alert("Position de l'utilisateur inconnue.");
    }
}

function quitNavigation() {
    try {
        stopMetzPolling(true);
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
        alert("Position de l'utilisateur inconnue.");
    }
}

async function findNearestParking() {
    try {
        const res = await fetch("api/parkings_geojson.php");
        const data = await res.json();

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
                    "Aucun parking compatible avec ces contraintes";
                return null;
            }
        } catch (e) {
            console.warn("apply param constraints failed", e);
        }

        candidates.sort((a, b) => a.distance - b.distance);

        const metzEnabled = document.getElementById("metzToggle")?.checked;
        if (!metzEnabled) {
            const nearest = candidates.length ? candidates[0].feature : null;
            document.getElementById("nearestParkingName").textContent = nearest
                ? (nearest.properties.lib || "Parking inconnu")
                : "Aucun parking trouvé";
            return nearest;
        }

        for (const c of candidates) {
            const feature = c.feature;
            const fid = feature.properties.fid || feature.properties.id || 0;
            if (!fid) continue;
            const info = await fetchMetzAvailabilityOnce(fid);
            if (info && info.value != null) {
                const num = Number(info.value);
                if (!Number.isNaN(num) && num > 0) {
                    document.getElementById("nearestParkingName").textContent =
                        feature.properties.lib || "Parking disponible";
                    return feature;
                }
            } else continue;
        }

        document.getElementById("nearestParkingName").textContent =
            "Aucun parking disponible (Metz)";
        return null;
    } catch (err) {
        console.error("Erreur lors du chargement des parkings :", err);
        document.getElementById("nearestParkingName").textContent =
            "Erreur de chargement";
    }
}

async function goToNearestParking() {
    try {
        const nearest = await findNearestParking();
        if (!nearest) {
            alert("Aucun parking trouvé pour ces critères.");
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
