<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <title>Carte des parkings</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }

        #map {
            flex: 1;
            height: 100%;
            width: 100%;
        }

        /* Conteneur principal pour la mise en page flex */
        .map-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100%;
            position: relative;
        }

        /* Conteneur du haut (panneau de guidage centré) */
        .top-center {
            position: absolute;
            top: 20px;

            /* centrer pc*/
            @media (min-width: 769px) {
                max-width: 30%;
                width: 30%;
                right: 35%;
            }

            /* centrer mobile*/
            @media (max-width: 768px) {
                left: 10vw;
                width: 80vw;
            }

            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1001;
            pointer-events: none;
            /* pour ne pas bloquer les clics sur la carte */
        }

        /* Style du panneau Leaflet Routing */
        .leaflet-routing-container {
            pointer-events: auto;
            display: none;
        }

        /* Supprime toutes les étapes sauf la première */
        .leaflet-routing-container .leaflet-routing-alt>table tr:not(:first-child) {
            display: none;
        }

        /* Bouton de recentrage en bas à gauche */
        .bottom-left {
            position: absolute;

            @media(min-width: 769px) {
                bottom: 2vw;
                left: 2vw;
            }

            @media(max-width: 768px) {
                bottom: 4vw;
                left: 2vw;
            }

            z-index: 1002;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        #btnCentrer {
            @media(min-width: 769px) {
                width: 3vw;
                height: 3vw;
            }

            @media(max-width: 768px) {
                width: 9vw;
                height: 9vw;
                font-size: 40px;
            }

            border-radius: 50%;
            border: none;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            font-size: 22px;
        }

        #btnCentrer:hover {
            transform: scale(1.1);
        }

        /* Follow button removed (simplified UI) */

        /* Compact routing action buttons added to routing panel */
        .routing-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .routing-actions .small-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            font-size: 16px;
        }

        .routing-actions .small-btn:hover {
            transform: scale(1.05);
        }

        /* Bouton de paramètre en haut à droite */
        .top-right {
            position: absolute;

            @media(min-width: 769px) {
                top: 1vw;
                right: 1vw;
            }

            @media(max-width: 768px) {
                bottom: 4vw;
                right: 2vw;
            }

            z-index: 1002;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        #parametre {
            @media(min-width: 769px) {
                width: 3vw;
                height: 3vw;
            }

            @media(max-width: 768px) {
                width: 9vw;
                height: 9vw;
                font-size: 40px;
            }

            border-radius: 10%;
            border: none;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            font-size: 22px;
        }

        /* ---- MENU PARAMÈTRES ---- */
        #menuParam {
            display: none;
            position: absolute;

            @media (min-width: 769px) {
                top: 23%;
                right: 35vw;
                width: 30vw;
                height: 45%;
            }

            @media (max-width: 768px) {
                top: 15%;
                right: 5vw;
                width: 80vw;
                height: 60%;
            }

            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.3);
            z-index: 2000;
            padding: 20px;
            flex-direction: column;
            gap: 15px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
            }

            to {
                transform: translateX(0);
            }
        }

        #menuParam h2 {
            margin: 0;
            text-align: center;
        }

        /* When a user is connected, enlarge the settings pane */
        #menuParam.connected {
            /* wider on desktop */
            right: 30vw;
            width: 40vw;
            height: 70%;
            top: 10%;
        }

        @media(max-width:768px) {
            #menuParam.connected {
                right: 5vw;
                width: 90vw;
                height: 85%;
                top: 10%;
            }
        }

        /* Vehicle/login UI removed: related styles cleaned up */

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
            border: none;
            background: none;
        }

        /* Bouton switch PMR */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            border-radius: 34px;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: #007bff;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .pmr-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Menu de guidage en bas au millieu */
        .menuGuider {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1002;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 1);

            @media(min-width: 769px) {
                height: 5vw;
                width: 20vw;
            }

            @media(max-width: 768px) {
                height: 10vw;
                width: 60vw;
            }
        }

        .menuGuider button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;

            @media(min-width: 769px) {
                height: 2vw;
                width: 8vw;
            }

            @media(max-width: 768px) {
                height: 4vw;
                width: 25vw;
            }
        }

        .menuGuider button:hover {
            transform: scale(1.1);
        }

        /* Login/auth UI removed */

        /* Better spacing inside the settings pane */
        #menuParam h3,
        #menuParam h4 {
            margin: 8px 0 6px;
            font-weight: 600;
        }

        #menuParam label {
            display: block;
            margin-bottom: 6px;
        }

        /* Make top-level direct children spaced when menu is shown as flex */
        #menuParam>* {
            margin-bottom: 8px;
        }

        /* Vehicle UI removed */

        .menuGuider h2 {
            margin: 0;
            font-size: 1.2em;
        }

        #btnGuider {
            background-color: rgba(78, 123, 30, 0.8);
        }

        #btnFermer {
            background-color: rgba(197, 35, 51, 0.8);
        }
    </style>
</head>

<body>
    <div class="map-container">
        <div id="map"></div>

        <!-- Bloc de guidage centré en haut -->
        <div class="top-center" id="guidage"></div>

        <!-- Bouton de recentrage -->
        <div class="bottom-left">
            <button id="btnCentrer" onclick="centrerPosition()" title="Centrer sur ma position">📍</button>
        </div>

        <!-- Guidage vers parking le plus proche -->
        <div class="menuGuider bottom-center">
            <h2 id="nearestParkingName"> </h2>
            <button id="btnGuider" onclick="goToNearestParking()" title="Allez">Allez</button>
            <button id="btnFermer" onclick="fermerGuidageAuto()" title="Fermer">Fermer</button>
        </div>

        <!-- Bouton de paramètres en haut à droite -->
        <div class="top-right">
            <button id="parametre">⚙️</button>
        </div>
        <!-- 🔧 MENU PARAMÈTRES -->
        <div id="menuParam">
            <button class="close-btn" onclick="fermerMenu()">❌</button>
            <h2>Paramètres</h2>

            <!-- parameters: PMR, Hauteur max, Électrique -->
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>PMR (mobilité réduite)</div>
                    <label class="switch">
                        <input type="checkbox" id="pmrToggle" onchange="togglePMR()">
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>Hauteur max (cm)</div>
                    <input type="number" id="heightMax" placeholder="ex: 200" style="width:90px;margin-left:8px;" />
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>Afficher / guider uniquement parkings électriques</div>
                    <label class="switch">
                        <input type="checkbox" id="electricToggle">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- Vérification disponibilité Metz -->
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span>Vérifier disponibilité Metz</span>
                <label class="switch" style="margin-left:8px;">
                    <input type="checkbox" id="metzToggle">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

    <script>
        let map, userMarker, currentLat, currentLon, routingControl, parkingsLayer;
        let pmr = false;
        // used for simple smoothing of noisy GPS
        let _smoothPrev = null;
        // Metz polling state
        let _metzPollInterval = null;
        let _metzPollMs = 5000; // 5s polling
        let _currentTargetFid = null;
        // Auto-switch throttle to avoid repeated automatic redirections
        let _lastAutoSwitchTs = 0;
        const AUTO_SWITCH_TTL = 30000; // ms between auto-switch attempts
        // Client-side cache for parkings geojson and Metz availability
        let lastGeoJson = null;
        const _metzClientCache = {}; // fid -> { ts, valueObj }
        const _metzClientCacheTtl = 10000; // ms
        const rayonKm = 50;

        // ---- MENU PARAM ----
        document.getElementById("parametre").addEventListener("click", () => {
            // hide the auto-guidance bar while adjusting settings
            try {
                const mg = document.querySelector('.menuGuider');
                if (mg) mg.style.display = 'none';
            } catch (e) { /* ignore */ }
            document.getElementById("menuParam").style.display = "flex";
            loadMenuSettings();
        });

        // Ferme le menu des paramètres et sauvegarde les préférences
        function fermerMenu() {
            document.getElementById("menuParam").style.display = "none";
            try {
                // Persist and refresh parameters, update map/guidance
                onParamChange();
            } catch (e) { /* ignore */ }

            // If there is no active navigation, restore the guider menu display
            try {
                if (!_currentTargetFid) {
                    const mg = document.querySelector('.menuGuider');
                    if (mg) mg.style.display = 'flex';
                }
            } catch (e) { /* ignore */ }
        }

        /* Menu settings (simplified) */
        function loadMenuSettings() {
            try {
                const pmrVal = localStorage.getItem('pmrEnabled');
                const heightVal = localStorage.getItem('heightMax');
                const elecVal = localStorage.getItem('electricOnly');
                if (document.getElementById('pmrToggle') && pmrVal !== null) document.getElementById('pmrToggle').checked = (pmrVal === '1');
                if (document.getElementById('heightMax') && heightVal !== null) document.getElementById('heightMax').value = heightVal;
                if (document.getElementById('electricToggle') && elecVal !== null) document.getElementById('electricToggle').checked = (elecVal === '1');
                // reflect pmr in global variable
                pmr = document.getElementById('pmrToggle')?.checked || false;
            } catch (e) { console.warn('loadMenuSettings', e); }
        }

        // Save simplified params to localStorage
        function saveParamSettings() {
            try {
                const pmrVal = document.getElementById('pmrToggle')?.checked ? '1' : '0';
                const heightVal = document.getElementById('heightMax')?.value || '';
                const elecVal = document.getElementById('electricToggle')?.checked ? '1' : '0';
                localStorage.setItem('pmrEnabled', pmrVal);
                localStorage.setItem('heightMax', heightVal);
                localStorage.setItem('electricOnly', elecVal);
                pmr = (pmrVal === '1');
            } catch (e) { console.warn('saveParamSettings', e); }
        }

        // Met à jour le flag PMR depuis l'UI
        function togglePMR() {
            pmr = document.getElementById("pmrToggle").checked;
            console.log("PMR =", pmr);
        }

        // Calcul distance (km)
        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(lat1 * Math.PI / 180) *
                Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async position => {
                currentLat = position.coords.latitude;
                currentLon = position.coords.longitude;

                map = L.map('map', {
                    minZoom: 3,
                    maxZoom: 19
                }).setView([currentLat, currentLon], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                    noWrap: true,
                }).addTo(map);

                map.setMaxBounds([[-90, -180], [90, 180]]);
                map.on('drag', () => {
                    map.panInsideBounds([[-90, -180], [90, 180]], { animate: false });
                });
                map.on('movestart', () => {
                });

                routingControl = L.Routing.control({
                    waypoints: [],
                    routeWhileDragging: true,
                    show: true,
                    lineOptions: {
                        styles: [{ color: 'blue', opacity: 0.6, weight: 3 }]
                    }
                }).addTo(map);

                // Déplacer le panneau Leaflet dans notre conteneur flexbox
                const routingContainer = document.querySelector('.leaflet-routing-container');
                document.getElementById('guidage').appendChild(routingContainer);

                // Add compact action button (quit) to routing panel
                try {
                    const actions = document.createElement('div');
                    actions.className = 'routing-actions';

                    const quitBtn = document.createElement('button');
                    quitBtn.className = 'small-btn';
                    quitBtn.title = 'Quitter la navigation';
                    quitBtn.innerText = '✖';
                    quitBtn.onclick = function () { quitNavigation(); };

                    actions.appendChild(quitBtn);
                    routingContainer.appendChild(actions);
                } catch (e) { console.warn('add routing action button failed', e); }

                userMarker = L.circleMarker([currentLat, currentLon], {
                    radius: 9,
                    color: 'white',
                    weight: 4,
                    fillColor: 'red',
                    fillOpacity: 1
                }).addTo(map)
                    .bindPopup("Vous êtes ici")
                    .openPopup();

                loadParkings();
                // load saved metz settings and wire save handlers
                loadMetzSettings();
                const mt = document.getElementById('metzToggle');
                const mu = document.getElementById('metzApiUrl');
                if (mt) mt.addEventListener('change', () => {
                    saveMetzSettings();
                    if (mt.checked && _currentTargetFid) startMetzPolling(_currentTargetFid);
                    if (!mt.checked) stopMetzPolling();
                    // refresh nearest parking suggestion when availability toggle changes
                    try { findNearestParking(); } catch (e) { console.warn('refresh nearest failed', e); }
                    // refresh displayed parkings to show only Metz parkings when enabled
                    try { loadParkings(); } catch (e) { console.warn('refresh parkings failed', e); }
                });
                if (mu) mu.addEventListener('change', saveMetzSettings);
                // initial nearest parking lookup so name is present on page load
                try { await findNearestParking(); } catch (e) { console.warn('initial nearest lookup failed', e); }

                navigator.geolocation.watchPosition(updatePosition());
            }, err => {
                alert("Impossible de vous localiser : " + err.message);
            });
        } else {
            alert("La géolocalisation n'est pas supportée par ce navigateur.");
        }

        // Mise à jour de la position utilisateur
        function updatePosition(pos) {
            currentLat = pos.coords.latitude;
            currentLon = pos.coords.longitude;
            const raw = [currentLat, currentLon];
            const latlng = smoothLatLng(raw, 0.25);
            if (userMarker) userMarker.setLatLng(latlng);


            // --- mise à jour throttled du départ de l'itinéraire (recalcule la route) ---
            try {
                if (!routingControl) return;
                if (!updatePosition._lastRoutingTs) updatePosition._lastRoutingTs = 0;
                const now = Date.now();
                const ROUTING_THROTTLE_MS = 2000; // ajuster si nécessaire
                if (now - updatePosition._lastRoutingTs < ROUTING_THROTTLE_MS) return;
                updatePosition._lastRoutingTs = now;

                const waypoints = routingControl.getWaypoints ? routingControl.getWaypoints() : null;
                // si on a une destination, on met à jour le départ vers la position courante
                if (waypoints && waypoints.length > 1 && waypoints[1] && waypoints[1].latLng) {
                    routingControl.setWaypoints([L.latLng(latlng[0], latlng[1]), waypoints[1].latLng]);
                }
            } catch (e) { console.warn('updatePosition routing update', e); }

        }

        // Recentre la carte avec un offset (pour afficher panneau UI)
        function centerWithOffset(latlng, offsetX = 0, offsetY = -100) {
            try {
                const p = map.latLngToContainerPoint(latlng);
                const pOffset = L.point(p.x + offsetX, p.y + offsetY);
                const latlngOffset = map.containerPointToLatLng(pOffset);
                map.setView(latlngOffset, map.getZoom(), { animate: true });
            } catch (e) {
                // map not ready or error
            }
        }

        // Lissage simple de la position GPS
        function smoothLatLng(newLatLng, alpha = 0.3) {
            if (_smoothPrev == null) _smoothPrev = newLatLng;
            if (!_smoothPrev) { _smoothPrev = newLatLng; return newLatLng; }
            _smoothPrev = [
                _smoothPrev[0] + alpha * (newLatLng[0] - _smoothPrev[0]),
                _smoothPrev[1] + alpha * (newLatLng[1] - _smoothPrev[1])
            ];
            return _smoothPrev;
        }

        // ----- Metz availability check -----
        // Persiste les préférences Metz (toggle + URL)
        function saveMetzSettings() {
            try {
                const enabled = document.getElementById('metzToggle')?.checked ? '1' : '0';
                const url = document.getElementById('metzApiUrl')?.value || '';
                localStorage.setItem('metzCheckEnabled', enabled);
                localStorage.setItem('metzApiUrl', url);
            } catch (e) { console.warn('saveMetzSettings', e); }
        }

        // Restaure préférences Metz depuis localStorage
        function loadMetzSettings() {
            try {
                const enabled = localStorage.getItem('metzCheckEnabled');
                const url = localStorage.getItem('metzApiUrl');
                if (document.getElementById('metzToggle') && enabled !== null) document.getElementById('metzToggle').checked = (enabled === '1');
                if (document.getElementById('metzApiUrl') && url !== null) document.getElementById('metzApiUrl').value = url;
            } catch (e) { console.warn('loadMetzSettings', e); }
        }

        // Construit l'URL externe pour interroger la disponibilité Metz (si configurée)
        function _getMetzRequestUrlForFid(fid) {
            const input = document.getElementById('metzApiUrl');
            if (!input) return null;
            let url = input.value.trim();
            if (!url) return null;
            if (url.includes('{fid}')) return url.replace('{fid}', encodeURIComponent(fid));
            // append parameter
            if (url.includes('?')) return url + '&fid=' + encodeURIComponent(fid);
            return url + '?fid=' + encodeURIComponent(fid);
        }

        // Affiche un badge avec l'information de disponibilité Metz dans le panneau de guidage
        function showMetzAvailability(text, cls = '') {
            try {
                const container = document.querySelector('.leaflet-routing-container');
                if (!container) return;
                let el = container.querySelector('.metz-availability');
                if (!el) {
                    el = document.createElement('div');
                    el.className = 'metz-availability';
                    el.style.padding = '6px 10px';
                    el.style.marginLeft = '8px';
                    el.style.background = 'rgba(255,255,255,0.9)';
                    el.style.borderRadius = '6px';
                    el.style.boxShadow = '0 1px 4px rgba(0,0,0,0.2)';
                    el.style.fontSize = '14px';
                    el.style.fontWeight = '600';
                    container.appendChild(el);
                }
                el.textContent = text;
                if (cls === 'ok') el.style.background = '#d4ffd7';
                else if (cls === 'warning') el.style.background = '#fff4cc';
                else if (cls === 'bad') el.style.background = '#ffd6d6';
                else el.style.background = 'rgba(255,255,255,0.9)';
            } catch (e) { console.warn('showMetzAvailability', e); }
        }

        // Récupère la disponibilité pour un parking (localement d'abord, sinon via URL externe)
        async function fetchMetzAvailabilityOnce(fid) {
            if (!fid) return null;
            const keys = ['available', 'free', 'places', 'available_places', 'disponible', 'nb_places', 'nombre', 'places_libres', 'place_libre'];
            const now = Date.now();

            // client-side cache check
            try {
                const c = _metzClientCache[fid];
                if (c && (now - c.ts) < _metzClientCacheTtl) return c.valueObj;
            } catch (e) { /* ignore */ }

            // 1) Try to read from client-side cached geojson (fast, local merge already included)
            try {
                let geo = lastGeoJson;
                // if we don't have lastGeoJson, try a single fetch as fallback
                if (!geo) {
                    try {
                        const r = await fetch('api/parkings_geojson.php');
                        if (r.ok) {
                            geo = await r.json();
                            lastGeoJson = geo;
                        }
                    } catch (e) {
                        // continue to external fallback
                    }
                }

                if (geo && Array.isArray(geo.features)) {
                    const feat = geo.features.find(f => {
                        const p = f.properties || {};
                        const fidProp = p.fid ?? p.id ?? null;
                        if (fidProp != null) return Number(fidProp) === Number(fid);
                        if (f.id && !isNaN(parseInt(f.id))) return Number(parseInt(f.id)) === Number(fid);
                        return false;
                    });
                    if (feat) {
                        const props = feat.properties || {};
                        for (const k of keys) {
                            if (props[k] !== undefined && props[k] !== null) {
                                const out = { raw: props, value: props[k], key: k };
                                _metzClientCache[fid] = { ts: now, valueObj: out };
                                return out;
                            }
                        }
                        // nested
                        for (const k of Object.keys(props)) {
                            const v = props[k];
                            if (v && typeof v === 'object') {
                                for (const kk of keys) {
                                    if (v[kk] !== undefined && v[kk] !== null) {
                                        const out = { raw: props, value: v[kk], key: kk };
                                        _metzClientCache[fid] = { ts: now, valueObj: out };
                                        return out;
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (e) {
                console.warn('fetchMetzAvailabilityOnce: local lookup failed', e);
            }

            // 2) Fallback to external user-configured URL (if any)
            const reqUrl = _getMetzRequestUrlForFid(fid);
            if (!reqUrl) return null;
            try {
                const res = await fetch(reqUrl);
                if (!res.ok) return null;
                const json = await res.json();
                // try to locate a sensible availability value in the JSON
                for (const k of keys) {
                    if (json[k] !== undefined && json[k] !== null) {
                        const out = { raw: json, value: json[k], key: k };
                        _metzClientCache[fid] = { ts: now, valueObj: out };
                        return out;
                    }
                }
                // try nested structures
                for (const k of Object.keys(json)) {
                    const v = json[k];
                    if (typeof v === 'object' && v !== null) {
                        for (const kk of keys) {
                            if (v && v[kk] !== undefined && v[kk] !== null) {
                                const out = { raw: json, value: v[kk], key: kk };
                                _metzClientCache[fid] = { ts: now, valueObj: out };
                                return out;
                            }
                        }
                    }
                }
                // fallback: return whole json and cache it
                const out = { raw: json, value: null, key: null };
                _metzClientCache[fid] = { ts: now, valueObj: out };
                return out;
            } catch (e) {
                console.warn('fetchMetzAvailabilityOnce', e);
                return null;
            }
        }

        // Démarre le polling périodique de disponibilité Metz pour le parking ciblé
        function startMetzPolling(fid) {
            stopMetzPolling();
            if (!fid) return;
            const enabled = document.getElementById('metzToggle')?.checked;
            if (!enabled) return;
            // immediate fetch
            (async () => {
                const info = await fetchMetzAvailabilityOnce(fid);
                if (info) {
                    if (info.value != null) showMetzAvailability('Places: ' + info.value, 'ok');
                    else showMetzAvailability('Info disponible', 'warning');
                    // if parking becomes full, attempt auto-switch
                    if (!isNaN(Number(info.value)) && Number(info.value) <= 0) {
                        attemptAutoSwitchIfNeeded();
                    }
                } else showMetzAvailability('Pas d\u00e9donnee', 'bad');
            })();
            _metzPollInterval = setInterval(async () => {
                const info = await fetchMetzAvailabilityOnce(fid);
                if (info) {
                    if (info.value != null) showMetzAvailability('Places: ' + info.value, 'ok');
                    else showMetzAvailability('Info disponible', 'warning');
                    // if parking becomes full, attempt auto-switch
                    if (!isNaN(Number(info.value)) && Number(info.value) <= 0) {
                        attemptAutoSwitchIfNeeded();
                    }
                } else showMetzAvailability('Pas de donn\u00e9e', 'bad');
            }, _metzPollMs);
        }

        // Arrête le polling Metz et nettoie l'indicateur
        function stopMetzPolling() {
            try {
                if (_metzPollInterval) { clearInterval(_metzPollInterval); _metzPollInterval = null; }
                const container = document.querySelector('.leaflet-routing-container');
                if (container) {
                    const el = container.querySelector('.metz-availability');
                    if (el) el.remove();
                }
                _currentTargetFid = null;
            } catch (e) { console.warn('stopMetzPolling', e); }
        }

        // Charge et affiche les parkings (filtrage local + option Metz)
        async function loadParkings() {
            try {
                const res = await fetch('api/parkings_geojson.php');
                if (!res.ok) throw new Error('parkings_geojson fetch failed');
                const data = await res.json();
                try { lastGeoJson = data; } catch (e) { /* ignore */ }

                // prepare features within radius first to minimize Metz API calls
                let features = (data.features || []).filter(feature => {
                    try {
                        const lat = feature.geometry.coordinates[1];
                        const lon = feature.geometry.coordinates[0];
                        return getDistance(currentLat, currentLon, lat, lon) <= rayonKm;
                    } catch (e) { return false; }
                });

                const metzEnabled = document.getElementById('metzToggle')?.checked;

                if (metzEnabled) {
                    // keep only parkings that exist in Metz availability source (local-first)
                    const kept = [];
                    for (const f of features) {
                        const fid = f.properties?.fid || f.properties?.id || (f.id && !isNaN(parseInt(f.id)) ? parseInt(f.id) : null);
                        if (!fid) continue;
                        try {
                            const info = await fetchMetzAvailabilityOnce(fid);
                            if (info) {
                                // attach availability info to feature properties for popup
                                f.properties = f.properties || {};
                                f.properties._metz_availability = info;
                                kept.push(f);
                            }
                        } catch (e) { console.warn('metz lookup failed for fid', fid, e); }
                    }
                    features = kept;
                } else {
                    // attach local availability fields if present
                    features = features.map(f => {
                        const props = f.properties || {};
                        // no change, but keep existing props
                        return f;
                    });
                }

                // apply local filters from simplified params: height, electric, pmr
                try {
                    const heightVal = document.getElementById('heightMax')?.value || '';
                    const heightReq = heightVal !== '' ? Number(heightVal) : null;
                    const electricOnly = document.getElementById('electricToggle')?.checked || false;
                    const pmrEnabled = pmr;
                    features = features.filter(f => {
                        try {
                            const props = f.properties || {};
                            if (heightReq != null && props.hauteur_max !== undefined && props.hauteur_max !== null && props.hauteur_max !== '') {
                                const pMax = Number(props.hauteur_max);
                                if (!isNaN(pMax) && pMax > 0 && heightReq > pMax) return false;
                            }
                            if (electricOnly) {
                                const pElec = props.electrique || props.electrique_capable || props.electrique_spots || null;
                                if (!pElec) return false;
                            }
                            if (pmrEnabled) {
                                const pPmr = props.pmr || props.access_pmr || props.pmr_accessible || null;
                                if (!pPmr) return false;
                            }
                            return true;
                        } catch (e) { return true; }
                    });
                } catch (e) { /* ignore */ }

                // clear previous layer if any
                try {
                    if (parkingsLayer) {
                        map.removeLayer(parkingsLayer);
                        parkingsLayer = null;
                    }
                } catch (e) { /* ignore */ }

                const geo = { type: 'FeatureCollection', features: features };
                parkingsLayer = L.geoJSON(geo, {
                    onEachFeature: (feature, layer) => {
                        const nom = feature.properties?.lib || 'Parking';
                        const fid = feature.properties?.fid || feature.properties?.id || (feature.id && !isNaN(parseInt(feature.id)) ? parseInt(feature.id) : 0);
                        // availability text if present
                        let availText = '';
                        const metz = feature.properties?._metz_availability;
                        if (metz) {
                            if (metz.value != null) availText = 'Places: ' + metz.value;
                            else availText = 'Info disponible';
                        } else {
                            // try to read local properties
                            const p = feature.properties || {};
                            const knownKeys = ['available', 'free', 'places', 'available_places', 'disponible', 'nb_places', 'places_libres', 'place_libre'];
                            for (const k of knownKeys) {
                                if (p[k] !== undefined && p[k] !== null) { availText = 'Places: ' + p[k]; break; }
                            }
                        }

                        const popupHtml = `
                            <div style="display:flex;flex-direction:column;gap:6px;max-width:240px;">
                                <div style="font-weight:700">${nom}</div>
                                ${availText ? (`<div style="font-size:0.95em;color:#333">${availText}</div>`) : ''}
                                <div style="display:flex;justify-content:flex-end;">
                                    <button title="Itinéraire" style="width:36px;height:32px;border-radius:6px;border:none;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.15);cursor:pointer;font-size:16px;" onclick="goToParking(${feature.geometry.coordinates[1]}, ${feature.geometry.coordinates[0]}, ${fid})">➡️</button>
                                </div>
                            </div>
                        `;

                        layer.bindPopup(popupHtml);
                    }
                }).addTo(map);

            } catch (err) {
                console.error('Erreur lors du chargement des parkings :', err);
            }
        }

        // Démarre le guidage vers un parking donné (et lance le polling Metz si activé)
        function goToParking(lat, lon, fid = null) {
            if (currentLat && currentLon) {
                routingControl.setWaypoints([
                    L.latLng(currentLat, currentLon),
                    L.latLng(lat, lon)
                ]);
                document.querySelector(".leaflet-routing-container").style.display = "flex";
                // remember target fid and start polling if user enabled Metz check
                _currentTargetFid = fid;
                if (document.getElementById('metzToggle') && document.getElementById('metzToggle').checked) {
                    startMetzPolling(fid);
                }
            } else {
                alert("Position de l'utilisateur inconnue.");
            }
        }

        // Quit navigation: stop routing, stop polling, show guider menu
        function quitNavigation() {
            try {
                stopMetzPolling();
                if (routingControl) routingControl.setWaypoints([]);
                const routingEl = document.querySelector('.leaflet-routing-container');
                if (routingEl) routingEl.style.display = 'none';
                // show guider menu to allow user to trigger again
                const mg = document.querySelector('.menuGuider');
                if (mg) mg.style.display = 'flex';
            } catch (e) { console.warn('quitNavigation', e); }
        }

        // Recentre la carte sur la position utilisateur
        function centrerPosition() {
            if (currentLat && currentLon) {
                map.setView([currentLat, currentLon], 16);
            } else {
                alert("Position de l'utilisateur inconnue.");
            }
        }

        // Recherche le parking le plus adapté selon paramètres et disponibilité Metz
        async function findNearestParking() {
            try {
                const res = await fetch('api/parkings_geojson.php');
                const data = await res.json();

                // build candidate list with distances
                let candidates = data.features.map(feature => {
                    const lat = feature.geometry.coordinates[1];
                    const lon = feature.geometry.coordinates[0];
                    const distance = getDistance(currentLat, currentLon, lat, lon);
                    return { feature, distance };
                }).filter(c => !isNaN(c.distance));

                // Apply simplified constraints coming from parameters: height, electricOnly, pmr
                try {
                    const heightVal = document.getElementById('heightMax')?.value || '';
                    const heightReq = heightVal !== '' ? Number(heightVal) : null;
                    const electricOnly = document.getElementById('electricToggle')?.checked || false;

                    candidates = candidates.filter(c => {
                        try {
                            const props = c.feature.properties || {};
                            // height constraint: if user provided a height and parking has hauteur_max
                            if (heightReq != null && props.hauteur_max !== undefined && props.hauteur_max !== null && props.hauteur_max !== '') {
                                const pMax = Number(props.hauteur_max);
                                if (!isNaN(pMax) && pMax > 0 && heightReq > pMax) return false;
                            }
                            // electric-only filter
                            if (electricOnly) {
                                const pElec = props.electrique || props.electrique_capable || props.electrique_spots || null;
                                if (!pElec) return false;
                            }
                            // PMR filter if enabled
                            if (pmr) {
                                const pPmr = props.pmr || props.access_pmr || props.pmr_accessible || null;
                                if (!pPmr) return false;
                            }
                            return true;
                        } catch (e) { return true; }
                    });

                    if (!candidates.length) {
                        document.getElementById("nearestParkingName").textContent = 'Aucun parking compatible avec ces contraintes';
                        return null;
                    }
                } catch (e) { console.warn('apply param constraints failed', e); }

                candidates.sort((a, b) => a.distance - b.distance);

                const metzEnabled = document.getElementById('metzToggle')?.checked;

                // If Metz check is disabled, pick the nearest directly
                if (!metzEnabled) {
                    const nearest = candidates.length ? candidates[0].feature : null;
                    document.getElementById("nearestParkingName").textContent = nearest ? (nearest.properties.lib || 'Parking inconnu') : 'Aucun parking trouvé';
                    return nearest;
                }

                // Metz check is enabled: iterate candidates by distance and pick first with availability > 0
                for (const c of candidates) {
                    const feature = c.feature;
                    const fid = feature.properties.fid || feature.properties.id || 0;
                    if (!fid) continue; // can't check
                    const info = await fetchMetzAvailabilityOnce(fid);
                    if (info && info.value != null) {
                        const num = Number(info.value);
                        if (!Number.isNaN(num) && num > 0) {
                            document.getElementById("nearestParkingName").textContent = feature.properties.lib || 'Parking disponible';
                            return feature;
                        }
                        // otherwise no free places, continue to next candidate
                    } else {
                        // If the API returned no usable data, skip this parking and continue
                        continue;
                    }
                }

                // no available parking found
                document.getElementById("nearestParkingName").textContent = 'Aucun parking disponible (Metz)';
                return null;
            } catch (err) {
                console.error("Erreur lors du chargement des parkings :", err);
                document.getElementById("nearestParkingName").textContent = "Erreur de chargement";
            }
        }

        // Bouton: démarre le guidage vers le parking le plus proche selon les paramètres
        async function goToNearestParking() {
            try {
                const nearest = await findNearestParking();
                if (!nearest) {
                    alert('Aucun parking trouvé pour ces critères.');
                    return;
                }
                const fid = nearest.properties?.fid || nearest.properties?.id || (nearest.id && !isNaN(parseInt(nearest.id)) ? parseInt(nearest.id) : null);
                goToParking(nearest.geometry.coordinates[1], nearest.geometry.coordinates[0], fid);
                // hide guider menu when navigation starts
                const mg = document.querySelector('.menuGuider');
                if (mg) mg.style.display = 'none';
            } catch (e) { console.warn('goToNearestParking', e); }
        }

        // Bouton: ferme le guidage automatique (équivalent à quitter la navigation)
        function fermerGuidageAuto() {
            try {
                quitNavigation();
            } catch (e) { console.warn('fermerGuidageAuto', e); }
        }

        // Helper to go to nearest parking is defined above; vehicle/auth code removed.

        // Attempt an automatic switch to the next nearest parking when current target is full.
        // Throttled by AUTO_SWITCH_TTL to avoid rapid repeated switches.
        async function attemptAutoSwitchIfNeeded() {
            try {
                const now = Date.now();
                if (now - _lastAutoSwitchTs < AUTO_SWITCH_TTL) return; // throttle
                _lastAutoSwitchTs = now;

                // If no active navigation, nothing to switch
                if (!_currentTargetFid) return;

                // Find nearest parking according to current params (this will prefer available ones if Metz enabled)
                const nearest = await findNearestParking();
                if (!nearest) return;

                const newFid = nearest.properties?.fid || nearest.properties?.id || (nearest.id && !isNaN(parseInt(nearest.id)) ? parseInt(nearest.id) : null);
                if (!newFid) return;

                // If it's the same as current target, nothing to do
                if (Number(newFid) === Number(_currentTargetFid)) return;

                // Switch destination silently
                goToParking(nearest.geometry.coordinates[1], nearest.geometry.coordinates[0], newFid);
            } catch (e) { console.warn('attemptAutoSwitchIfNeeded', e); }
        }

        // When parameters change: save, refresh map layer and update nearest/guidage if active
        async function onParamChange() {
            try {
                // persist params and refresh map
                saveParamSettings();
                await loadParkings();

                // refresh nearest suggestion
                const nearest = await findNearestParking();

                // If a navigation is already active, update its target to the new nearest
                if (_currentTargetFid && nearest && nearest.geometry && nearest.geometry.coordinates) {
                    const fid = nearest.properties?.fid || nearest.properties?.id || (nearest.id && !isNaN(parseInt(nearest.id)) ? parseInt(nearest.id) : null);
                    goToParking(nearest.geometry.coordinates[1], nearest.geometry.coordinates[0], fid);
                }
            } catch (e) { console.warn('onParamChange', e); }
        }

        // Attach listeners to params so changes immediately update map and guidance
        try {
            const elPmr = document.getElementById('pmrToggle');
            const elHeight = document.getElementById('heightMax');
            const elElec = document.getElementById('electricToggle');
            if (elPmr) elPmr.addEventListener('change', () => { togglePMR(); onParamChange(); });
            if (elHeight) elHeight.addEventListener('input', () => { /* live update while typing */ onParamChange(); });
            if (elHeight) elHeight.addEventListener('change', () => { onParamChange(); });
            if (elElec) elElec.addEventListener('change', () => { onParamChange(); });
        } catch (e) { console.warn('attach param listeners failed', e); }
    </script>
</body>

</html>