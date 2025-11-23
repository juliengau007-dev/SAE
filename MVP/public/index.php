<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Carte des parkings</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        /* Base layout */
        /* Use border-box everywhere to make %/padding sizing predictable */
        *,
        *:before,
        *:after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        .map-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100%;
            position: relative;
        }

        #map {
            flex: 1;
            height: 100%;
            width: 100%;
            min-height: 200px;
        }

        /* Top centered guidance container */
        .top-center {
            position: absolute;
            top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1001;
            pointer-events: none;
            width: 40%;
            left: 30%;
        }

        /* Leaflet routing panel adjustments */
        .leaflet-routing-container {
            pointer-events: auto;
            display: none;
            position: relative;
            /* permet le positionnement absolu des éléments internes */
        }

        /* Place les actions (bouton quitter, etc.) en haut à droite du panneau de routage */
        .leaflet-routing-container .routing-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 1003;
            display: flex;
            gap: 6px;
            align-items: center;
            pointer-events: auto;
        }

        /* Centrer l'affichage de disponibilité Metz au milieu du panneau de routage */
        .leaflet-routing-container .metz-availability {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 1002;
            pointer-events: none;
            /* évite d'interférer avec les boutons */
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 600;
        }

        .leaflet-routing-container .leaflet-routing-alt>table tr:not(:first-child) {
            display: none;
        }

        .leaflet-routing-collapse-btn {
            display: none;
        }

        .leaflet-popup-content-wrapper {}

        /* Bottom-left recenter button container */
        .bottom-left {
            bottom: 2%;
            left: 1%;
            position: fixed;
            z-index: 1002;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        #btnCentrer {
            border-radius: 50%;
            border: none;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            font-size: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #btnCentrer:hover {
            transform: scale(1.05);
        }

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

        .metz-availability {
            font-size: 14px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            height: 28%;
        }

        /* Metz availability display */
        .routing-actions .metz-availability {
            align-items: center;
            padding: 6px 10px;
            margin-left: 8px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }



        /* Top-right parameter button */
        .top-right {
            position: fixed;
            z-index: 1002;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        #parametre {
            border-radius: 10%;
            border: none;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            font-size: 22px;
        }

        /* Settings panel */
        #menuParam {
            display: none;
            position: fixed;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.3);
            z-index: 2000;
            padding: 20px;
            flex-direction: column;
            gap: 15px;
            animation: slideIn 0.3s ease;
            overflow: auto;
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

        #menuParam.connected {
            /* will be overridden per breakpoint */
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
            border: none;
            background: none;
        }

        /* Toggle switch */
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

        /* Bottom centered guidance menu */
        .menuGuider {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1002;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 10px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.12);
        }

        .menuGuider h2 {
            margin: 0;
            font-size: 1.2em;
            text-align: center;
        }

        .menuGuider button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
            cursor: pointer;
        }

        .menuGuider button:hover {
            transform: scale(1.05);
        }

        /* Settings pane spacing */
        #menuParam h3,
        #menuParam h4 {
            margin: 8px 0 6px;
            font-weight: 600;
        }

        #menuParam label {
            display: block;
            margin-bottom: 6px;
        }

        #menuParam>* {
            margin-bottom: 8px;
        }

        /* Small utility buttons colors */
        #btnGuider {
            background-color: rgba(78, 123, 30, 0.8);
        }

        #btnFermer {
            background-color: rgba(197, 35, 51, 0.8);
        }

        #btnCentrer {
            font-size: 18px;
        }

        .top-right {
            top: 1%;
            right: 1%;
        }

        #menuParam {
            top: 28%;
            right: 20%;
            width: 60%;
            height: 44%;
        }

        .menuGuider {
            width: 30%;
            padding: 12px;
            left: 50%;
            transform: translateX(-50%);
            box-sizing: border-box;
            bottom: 3%;
        }

        /* Responsive breakpoints */
        @media (max-width: 768px) {
            .top-center {
                left: 10%;
                width: 80%;
                pointer-events: auto;
                z-index: 1005;
            }

            .bottom-left {
                bottom: 3%;
                left: 2%;
                z-index: 1005;
            }

            #btnCentrer {
                font-size: 20px;
                padding: 0;
            }

            .top-right {
                bottom: 3%;
                right: 2%;
                z-index: 1005;
            }

            #parametre {
                font-size: 20px;
            }

            #menuParam {
                top: 25%;
                right: 5%;
                width: 90%;
                height: 50%;
            }

            .menuGuider {
                height: auto;
                width: 70%;
                flex-wrap: wrap;
            }

            .menuGuider h2 {
                flex: 1 1 60%;
                font-size: 1em;
                margin-right: 8px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .menuGuider button {
                flex: 0 0 36%;
                max-width: 36%;
                min-width: 90px;
                padding: 8px 10px;
                font-size: 16px;
                box-sizing: border-box;
            }

            .leaflet-routing-container {
                left: 5%;
            }
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
                    <div>Véhicules électriques</div>
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
        // Variables globales principales
        let map, userMarker, currentLat, currentLon, routingControl, parkingsLayer;
        let pmr = false; // filtre personnes à mobilité réduite activé

        /*
         * État utilisé pour un lissage simple des positions GPS
         * afin de réduire les sauts visuels lorsque le signal est bruité.
         */
        let _smoothPrev = null;

        // État et paramètres du polling pour l'API de disponibilités (Metz)
        let _metzPollInterval = null;
        let _metzPollMs = 5000; // période de polling en ms (5s)
        let _currentTargetFid = null; // identifiant du parking ciblé

        // Protection contre les basculements automatiques trop fréquents
        let _lastAutoSwitchTs = 0;
        const AUTO_SWITCH_TTL = 30000; // délai minimal entre basculements automatiques (ms)

        // Cache côté client pour limiter les requêtes répétées
        let lastGeoJson = null; // dernier GeoJSON local chargé
        const _metzClientCache = {}; // structure: fid -> { ts, valueObj }
        const _metzClientCacheTtl = 10000; // durée de validité du cache (ms)

        // Rayon d'intérêt autour de l'utilisateur (en kilomètres)
        const rayonKm = 50;

        // Zoom utilisé systématiquement pour le recentrage et le suivi
        const defaultZoom = 16;

        // Indique si l'utilisateur interagit manuellement avec la carte
        // (éviter que l'auto-follow reprenne la main pendant une consultation)
        let _userInteracting = false;

        // --------- GESTION INTERFACE / PARAMETRES ---------
        // Ouvre le panneau de paramètres
        document.getElementById("parametre").addEventListener("click", () => {
            // masquer le panneau de guidage automatique pendant l'édition
            try {
                const mg = document.querySelector('.menuGuider');
                if (mg) mg.style.display = 'none';
            } catch (e) { /* si échec, on ignore */ }
            document.getElementById("menuParam").style.display = "flex";
            loadMenuSettings();
        });

        /**
         * fermerMenu()
         * Ferme le panneau des paramètres et applique les changements
         * (persistance + actualisation des couches et suggestions).
         */
        function fermerMenu() {
            document.getElementById("menuParam").style.display = "none";
            try { onParamChange(); } catch (e) { /* ignore */ }


            // si aucune navigation active, restaurer l'affichage du bandeau guide
            // vérifier explicitement null/undefined : un fid == 0 doit rester actif
            try {
                if (_currentTargetFid == null) {
                    const mg = document.querySelector('.menuGuider');
                    if (mg) mg.style.display = 'flex';
                }
            } catch (e) { /* ignore */ }
        }

        /**
         * loadMenuSettings()
         * Charge les paramètres sauvegardés dans localStorage et met à
         * jour l'UI (checkboxes, champs) ainsi que la variable `pmr`.
         */
        function loadMenuSettings() {
            try {
                const pmrVal = localStorage.getItem('pmrEnabled');
                const heightVal = localStorage.getItem('heightMax');
                const elecVal = localStorage.getItem('electricOnly');
                if (document.getElementById('pmrToggle') && pmrVal !== null) document.getElementById('pmrToggle').checked = (pmrVal === '1');
                if (document.getElementById('heightMax') && heightVal !== null) document.getElementById('heightMax').value = heightVal;
                if (document.getElementById('electricToggle') && elecVal !== null) document.getElementById('electricToggle').checked = (elecVal === '1');
                // synchroniser le flag global pmr
                pmr = document.getElementById('pmrToggle')?.checked || false;
            } catch (e) { console.warn('loadMenuSettings', e); }
        }

        /**
         * saveParamSettings()
         * Persiste les paramètres saisis dans localStorage.
         */
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

        // Met à jour le flag PMR depuis l'UI (callback)
        function togglePMR() {
            pmr = document.getElementById("pmrToggle").checked;
        }

        // --------- UTILITAIRES GÉOGRAPHIQUES ---------
        /**
         * getDistance(lat1, lon1, lat2, lon2)
         * Calcule la distance en kilomètres entre deux points (formule haversine).
         */
        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // rayon Terre en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(lat1 * Math.PI / 180) *
                Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // --------- INITIALISATION carte et géolocalisation ---------
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async position => {
                // position initiale de l'utilisateur
                currentLat = position.coords.latitude;
                currentLon = position.coords.longitude;

                // initialisation de la carte Leaflet centrée sur l'utilisateur
                map = L.map('map', { minZoom: 3, maxZoom: 19 }).setView([currentLat, currentLon], defaultZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                    noWrap: true,
                }).addTo(map);

                // éviter de sortir du monde (limites globales)
                map.setMaxBounds([[-90, -180], [90, 180]]);
                map.on('drag', () => map.panInsideBounds([[-90, -180], [90, 180]], { animate: false }));
                // Détecter quand l'utilisateur interagit avec la carte afin de
                // ne pas forcer le recentrage automatique pendant sa consultation.
                map.on('movestart', () => { _userInteracting = true; });
                map.on('moveend', () => { _userInteracting = false; });

                // contrôle d'itinéraire (Leaflet Routing Machine)
                routingControl = L.Routing.control({
                    router: L.Routing.osrmv1({ language: 'fr' }),
                    waypoints: [],
                    routeWhileDragging: true,
                    show: true,
                    lineOptions: { styles: [{ color: 'blue', opacity: 0.6, weight: 3 }] },
                    formatter: new L.Routing.Formatter({ language: 'fr', units: 'metric' }),
                    // on crée des marqueurs invisibles pour start/end (UI personnalisée ailleurs)
                    createMarker: function (i, wp, nWps) {
                        return L.marker(wp.latLng, { opacity: 0, interactive: false, keyboard: false, draggable: false });
                    }
                }).addTo(map);

                /*
                 * Certains routers renvoient des instructions en anglais.
                 * translateRoutingInstructions() post-traite le texte des
                 * instructions pour remplacer les phrases courantes par du français.
                 */
                function translateRoutingInstructions() {
                    const DICT = [
                        [/roundabout/ig, 'Rond-point'],
                        [/enter roundabout/ig, 'Entrez au rond-point'],
                        [/exit at roundabout/ig, 'Prenez la sortie'],
                        [/take the exit/ig, 'Prenez la sortie'],
                        [/turn right/ig, 'Tournez à droite'],
                        [/turn left/ig, 'Tournez à gauche'],
                        [/continue/ig, 'Continuez'],
                        [/slight right/ig, 'Légèrement à droite'],
                        [/slight left/ig, 'Légèrement à gauche'],
                        [/make a U-turn/ig, 'Faites demi-tour'],
                        [/you have arrived/ig, 'Vous êtes arrivé(e)'],
                        [/arrive at your destination/ig, 'Vous êtes arrivé(e) à destination'],
                        [/merge/ig, 'Rapprochez-vous et fusionnez'],
                        [/keep left/ig, 'Restez à gauche'],
                        [/keep right/ig, 'Restez à droite']
                    ];

                    document.querySelectorAll('.leaflet-routing-instruction-text').forEach(el => {
                        try {
                            let s = el.innerText || el.textContent || '';
                            DICT.forEach(([re, fr]) => { s = s.replace(re, fr); });
                            if (s && s !== (el.innerText || '')) el.innerText = s;
                        } catch (e) { /* ignorer les erreurs élément par élément */ }
                    });
                }

                // écouter les événements de routage pour traduire les instructions
                if (routingControl && routingControl.on) {
                    routingControl.on('routesfound', () => setTimeout(translateRoutingInstructions, 50));
                    routingControl.on('routeselected', () => setTimeout(translateRoutingInstructions, 50));
                }

                // déplacer le panneau de routage dans notre conteneur de guidage
                const routingContainer = document.querySelector('.leaflet-routing-container');
                document.getElementById('guidage').appendChild(routingContainer);

                // ajouter un petit bouton pour quitter la navigation dans le panneau
                try {
                    const actions = document.createElement('div');
                    actions.className = 'routing-actions';
                    const quitBtn = document.createElement('button');
                    quitBtn.className = 'small-btn';
                    quitBtn.title = 'Quitter la navigation';
                    quitBtn.innerText = '✖';
                    quitBtn.onclick = function () {
                        quitNavigation();
                    };
                    actions.appendChild(quitBtn);
                    routingContainer.appendChild(actions);
                } catch (e) { console.warn('add routing action button failed', e); }

                // marqueur représentant l'utilisateur
                userMarker = L.circleMarker([currentLat, currentLon], { radius: 9, color: 'white', weight: 4, fillColor: 'red', fillOpacity: 1 }).addTo(map).openPopup();

                // charger les paramètres et parkings sauvegardés
                // charger d'abord les paramètres UI pour qu'ils s'appliquent
                loadMenuSettings();
                loadMetzSettings();
                loadParkings();
                const mt = document.getElementById('metzToggle');
                const mu = document.getElementById('metzApiUrl');
                if (mt) mt.addEventListener('change', () => {
                    saveMetzSettings();
                    if (mt.checked && _currentTargetFid != null) startMetzPolling(_currentTargetFid);
                    if (!mt.checked) stopMetzPolling();
                    try { findNearestParking(); } catch (e) { console.warn('refresh nearest failed', e); }
                    try { loadParkings(); } catch (e) { console.warn('refresh parkings failed', e); }
                });
                if (mu) mu.addEventListener('change', saveMetzSettings);

                // recherche initiale du parking le plus proche (afin d'initialiser l'UI)
                try { await findNearestParking(); } catch (e) { console.warn('initial nearest lookup failed', e); }

                // démarrer la mise à jour continue de la position utilisateur
                // Demander des positions en haute précision et éviter les valeurs trop anciennes
                navigator.geolocation.watchPosition(
                    updatePosition,
                    err => { console.warn('watchPosition error', err); },
                    { enableHighAccuracy: true, maximumAge: 500, timeout: 5000 }
                );
            }, err => {
                alert("Impossible de vous localiser : " + err.message);
            });
        } else {
            alert("La géolocalisation n'est pas supportée par ce navigateur.");
        }

        // --------- GESTION POSITION UTILISATEUR ---------
        /**
         * updatePosition(pos)
         * Callback appelé par la géolocalisation lorsque la position change.
         * - met à jour currentLat/currentLon
         * - applique un lissage visuel
         * - met à jour le marqueur utilisateur
         * - ajuste l'itinéraire (si actif) de façon throttlée
         */
        function updatePosition(pos) {
            currentLat = pos.coords.latitude;
            currentLon = pos.coords.longitude;
            const latlng = [currentLat, currentLon];
            if (userMarker) userMarker.setLatLng(latlng);

            // Recalcul du point de départ de l'itinéraire (throttlé pour limiter les requêtes)
            try {
                if (!routingControl) return;
                if (!updatePosition._lastRoutingTs) updatePosition._lastRoutingTs = 0;
                const now = Date.now();
                const ROUTING_THROTTLE_MS = 500; // minimum entre recalculs d'itinéraire (ms)
                if (now - updatePosition._lastRoutingTs < ROUTING_THROTTLE_MS) return;
                updatePosition._lastRoutingTs = now;

                const waypoints = routingControl.getWaypoints ? routingControl.getWaypoints() : null;
                // si une destination est définie, mettre à jour le point de départ
                if (waypoints && waypoints.length > 1 && waypoints[1] && waypoints[1].latLng) {
                    routingControl.setWaypoints([L.latLng(latlng[0], latlng[1]), waypoints[1].latLng]);
                    // Recentre la carte automatiquement sur la position courante
                    // uniquement si l'utilisateur n'est pas en train d'interagir
                    // avec la carte (évite de reprendre la main quand il consulte).
                    try {
                        if (!_userInteracting) centerWithOffset(L.latLng(latlng[0], latlng[1]));
                    } catch (e) { /* ignore centering failures */ }
                }
            } catch (e) { console.warn('updatePosition routing update', e); }
        }

        /**
         * centerWithOffset(latlng, offsetX, offsetY)
         * Centre la carte sur une position en appliquant un décalage en pixels
         * (utile pour laisser la place au panneau d'UI affiché).
         */
        function centerWithOffset(latlng, offsetX = 0, offsetY = -3) {
            try {
                const p = map.latLngToContainerPoint(latlng);
                const pOffset = L.point(p.x + offsetX, p.y + offsetY);
                const latlngOffset = map.containerPointToLatLng(pOffset);
                map.setView(latlngOffset, defaultZoom, { animate: false });
            } catch (e) { /* map pas prête ou erreur */ }
        }

        // --------- GESTION DONNÉES / METZ AVAILABILITY ---------
        /**
         * saveMetzSettings()
         * Sauvegarde localement les préférences liées au service Metz.
         */
        function saveMetzSettings() {
            try {
                const enabled = document.getElementById('metzToggle')?.checked ? '1' : '0';
                const url = document.getElementById('metzApiUrl')?.value || '';
                localStorage.setItem('metzCheckEnabled', enabled);
                localStorage.setItem('metzApiUrl', url);
            } catch (e) { console.warn('saveMetzSettings', e); }
        }

        /**
         * loadMetzSettings()
         * Restaure les paramètres Metz depuis localStorage (si présents).
         */
        function loadMetzSettings() {
            try {
                const enabled = localStorage.getItem('metzCheckEnabled');
                const url = localStorage.getItem('metzApiUrl');
                if (document.getElementById('metzToggle') && enabled !== null) document.getElementById('metzToggle').checked = (enabled === '1');
                if (document.getElementById('metzApiUrl') && url !== null) document.getElementById('metzApiUrl').value = url;
            } catch (e) { console.warn('loadMetzSettings', e); }
        }

        /**
         * _getMetzRequestUrlForFid(fid)
         * Construit l'URL d'appel externe pour interroger la disponibilité
         * d'un parking (si l'utilisateur a renseigné une URL).
         */
        function _getMetzRequestUrlForFid(fid) {
            const input = document.getElementById('metzApiUrl');
            if (!input) return null;
            let url = input.value.trim();
            if (!url) return null;
            if (url.includes('{fid}')) return url.replace('{fid}', encodeURIComponent(fid));
            if (url.includes('?')) return url + '&fid=' + encodeURIComponent(fid);
            return url + '?fid=' + encodeURIComponent(fid);
        }

        /**
         * showMetzAvailability(text, cls)
         * Affiche un petit badge d'information dans le panneau de routage.
         */
        function showMetzAvailability(text, cls = '') {
            try {
                const container = document.querySelector('.leaflet-routing-container');
                if (!container) return;
                let el = container.querySelector('.metz-availability');
                if (!el) { el = document.createElement('div'); el.className = 'metz-availability'; container.appendChild(el); }
                el.textContent = text;
                if (cls === 'ok') el.style.background = '#d4ffd7';
                else if (cls === 'warning') el.style.background = '#fff4cc';
                else if (cls === 'bad') el.style.background = '#ffd6d6';
                else el.style.background = 'rgba(255,255,255,0.9)';
            } catch (e) { console.warn('showMetzAvailability', e); }
        }

        /**
         * fetchMetzAvailabilityOnce(fid)
         * Tente de retrouver la disponibilité d'un parking :
         * 1) depuis le geojson local chargé (rapide, priorité)
         * 2) sinon via l'URL externe configurée par l'utilisateur
         * Résultat : objet { raw, value, key } ou null.
         */
        async function fetchMetzAvailabilityOnce(fid) {
            if (!fid) return null;
            const keys = ['available', 'free', 'places', 'available_places', 'disponible', 'nb_places', 'nombre', 'places_libres', 'place_libre'];
            const now = Date.now();

            // Vérification cache côté client
            try { const c = _metzClientCache[fid]; if (c && (now - c.ts) < _metzClientCacheTtl) return c.valueObj; } catch (e) { /* ignore */ }

            // 1) recherche locale dans lastGeoJson
            try {
                let geo = lastGeoJson;
                if (!geo) {
                    try { const r = await fetch('api/parkings_geojson.php'); if (r.ok) { geo = await r.json(); lastGeoJson = geo; } } catch (e) { /* fallback to external */ }
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
                        for (const k of keys) { if (props[k] !== undefined && props[k] !== null) { const out = { raw: props, value: props[k], key: k }; _metzClientCache[fid] = { ts: now, valueObj: out }; return out; } }
                        // recherche dans les objets imbriqués
                        for (const k of Object.keys(props)) {
                            const v = props[k];
                            if (v && typeof v === 'object') {
                                for (const kk of keys) { if (v[kk] !== undefined && v[kk] !== null) { const out = { raw: props, value: v[kk], key: kk }; _metzClientCache[fid] = { ts: now, valueObj: out }; return out; } }
                            }
                        }
                    }
                }
            } catch (e) { console.warn('fetchMetzAvailabilityOnce: local lookup failed', e); }

            // 2) fallback vers URL utilisateur
            const reqUrl = _getMetzRequestUrlForFid(fid);
            if (!reqUrl) return null;
            try {
                const res = await fetch(reqUrl);
                if (!res.ok) return null;
                const json = await res.json();
                for (const k of keys) { if (json[k] !== undefined && json[k] !== null) { const out = { raw: json, value: json[k], key: k }; _metzClientCache[fid] = { ts: now, valueObj: out }; return out; } }
                for (const k of Object.keys(json)) {
                    const v = json[k];
                    if (typeof v === 'object' && v !== null) {
                        for (const kk of keys) { if (v && v[kk] !== undefined && v[kk] !== null) { const out = { raw: json, value: v[kk], key: kk }; _metzClientCache[fid] = { ts: now, valueObj: out }; return out; } }
                    }
                }
                const out = { raw: json, value: null, key: null };
                _metzClientCache[fid] = { ts: now, valueObj: out };
                return out;
            } catch (e) { console.warn('fetchMetzAvailabilityOnce', e); return null; }
        }

        /**
         * startMetzPolling(fid)
         * Lance un polling périodique pour la disponibilité du parking ciblé
         * (uniquement si l'utilisateur a activé la vérification Metz).
         */
        function startMetzPolling(fid) {
            // arrêter le polling précédent puis préserver le fid ciblé
            stopMetzPolling();
            if (!fid) return;
            _currentTargetFid = fid; // stopMetzPolling() réinitialise le fid, on le restaure
            const enabled = document.getElementById('metzToggle')?.checked;
            if (!enabled) { _currentTargetFid = null; return; }


            // fetch initial
            (async () => {
                const info = await fetchMetzAvailabilityOnce(fid);
                if (info) {
                    if (info.value != null) showMetzAvailability('Places: ' + info.value, 'ok');
                    else showMetzAvailability('Info disponible', 'warning');
                    if (!isNaN(Number(info.value)) && Number(info.value) <= 0) attemptAutoSwitchIfNeeded();
                } else showMetzAvailability('Pas de donnée', 'bad');
            })();

            _metzPollInterval = setInterval(async () => {
                const info = await fetchMetzAvailabilityOnce(fid);
                if (info) {
                    if (info.value != null) showMetzAvailability('Places: ' + info.value, 'ok');
                    else showMetzAvailability('Info disponible', 'warning');
                    if (!isNaN(Number(info.value)) && Number(info.value) <= 0) attemptAutoSwitchIfNeeded();
                } else showMetzAvailability('Pas de donnée', 'bad');
            }, _metzPollMs);
        }

        // stopMetzPolling(clearTarget = false)
        // Arrête le polling et nettoie l'affichage. Par défaut, ne réinitialise
        // pas `_currentTargetFid` pour ne pas interrompre une navigation déjà
        // en cours lorsque l'utilisateur désactive simplement la vérif Metz.
        // Si `clearTarget` est true, la cible est réinitialisée (utilisé lors
        // de l'arrêt complet de la navigation).
        function stopMetzPolling(clearTarget = false) {
            try {
                if (_metzPollInterval) {
                    clearInterval(_metzPollInterval);
                    _metzPollInterval = null;
                }
                const container = document.querySelector('.leaflet-routing-container');
                if (container) {
                    const el = container.querySelector('.metz-availability');
                    if (el) el.remove();
                }
                if (clearTarget) _currentTargetFid = null;
            } catch (e) {
                console.warn('stopMetzPolling', e);
            }
        }

        /**
         * loadParkings()
         * Charge le GeoJSON local via `api/parkings_geojson.php`, applique
         * un filtrage géographique (rayon) puis des filtres usuels
         * (hauteur, électrique, PMR). Les données Metz éventuelles
         * sont rattachées si l'option est activée.
         */
        async function loadParkings() {
            try {
                const res = await fetch('api/parkings_geojson.php');
                if (!res.ok) throw new Error('parkings_geojson fetch failed');
                const data = await res.json();
                try { lastGeoJson = data; } catch (e) { /* ignore */ }

                // réduire la liste aux parkings dans un rayon donné pour limiter les appels Metz
                let features = (data.features || []).filter(feature => {
                    try { const lat = feature.geometry.coordinates[1]; const lon = feature.geometry.coordinates[0]; return getDistance(currentLat, currentLon, lat, lon) <= rayonKm; } catch (e) { return false; }
                });

                const metzEnabled = document.getElementById('metzToggle')?.checked;

                if (metzEnabled) {
                    const kept = [];
                    for (const f of features) {
                        const fid = f.properties?.fid || f.properties?.id || (f.id && !isNaN(parseInt(f.id)) ? parseInt(f.id) : null);
                        if (!fid) continue;
                        try { const info = await fetchMetzAvailabilityOnce(fid); if (info) { f.properties = f.properties || {}; f.properties._metz_availability = info; kept.push(f); } } catch (e) { console.warn('metz lookup failed for fid', fid, e); }
                    }
                    features = kept;
                }

                // application des filtres locaux (hauteur, électrique, PMR)
                try {
                    const heightVal = document.getElementById('heightMax')?.value || '';
                    const heightReq = heightVal !== '' ? Number(heightVal) : null;
                    const electricOnly = document.getElementById('electricToggle')?.checked || false;
                    const pmrEnabled = pmr;
                    features = features.filter(f => {
                        try {
                            const props = f.properties || {};
                            if (heightReq != null && props.hauteur_max !== undefined && props.hauteur_max !== null && props.hauteur_max !== '') { const pMax = Number(props.hauteur_max); if (!isNaN(pMax) && pMax > 0 && heightReq > pMax) return false; }
                            if (electricOnly) { const pElec = props.electrique || props.electrique_capable || props.electrique_spots || null; if (!pElec) return false; }
                            if (pmrEnabled) { const pPmr = props.pmr || props.access_pmr || props.pmr_accessible || null; if (!pPmr) return false; }
                            if (_currentTargetFid != null) {
                                const fid = props?.fid || props?.id || (f.id && !isNaN(parseInt(f.id)) ? parseInt(f.id) : null);
                                if (fid != null && Number(fid) === Number(_currentTargetFid)) return true;
                                return false;
                            }
                            return true;
                        } catch (e) { return true; }
                    });
                } catch (e) { /* ignore */ }

                // suppression de l'ancienne couche
                try { if (parkingsLayer) { map.removeLayer(parkingsLayer); parkingsLayer = null; } } catch (e) { /* ignore */ }

                const geo = { type: 'FeatureCollection', features: features };
                parkingsLayer = L.geoJSON(geo, {
                    onEachFeature: (feature, layer) => {
                        const nom = feature.properties?.lib || 'Parking';
                        const fid = feature.properties?.fid || feature.properties?.id || (feature.id && !isNaN(parseInt(feature.id)) ? parseInt(feature.id) : 0);
                        let availText = '';
                        const metz = feature.properties?._metz_availability;
                        if (metz) { if (metz.value != null) availText = 'Places: ' + metz.value; else availText = 'Info disponible'; }
                        else { const p = feature.properties || {}; const knownKeys = ['available', 'free', 'places', 'available_places', 'disponible', 'nb_places', 'places_libres', 'place_libre']; for (const k of knownKeys) { if (p[k] !== undefined && p[k] !== null) { availText = 'Places: ' + p[k]; break; } } }

                        const popupHtml = `
                            <div style="display:flex;flex-direction:column;gap:6px;max-width:240px;">
                                <div style="font-weight:700">${nom}</div>
                                ${availText ? (`<div style="font-size:0.95em;color:#333">${availText}</div>`) : ''}
                                <div style="display:flex;justify-content:center;">
                                    <button title="Itinéraire" style="width:36px;height:32px;border-radius:6px;border:none;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.15);cursor:pointer;font-size:16px;" onclick="goToParking(${feature.geometry.coordinates[1]}, ${feature.geometry.coordinates[0]}, ${fid})">➡️</button>
                                </div>
                            </div>
                        `;

                        layer.bindPopup(popupHtml);
                    }
                }).addTo(map);

            } catch (err) { console.error('Erreur lors du chargement des parkings :', err); }
        }

        /**
         * goToParking(lat, lon, fid)
         * Démarre le guidage vers un point donné et lance le polling Metz
         * si l'option est activée.
         */
        function goToParking(lat, lon, fid) {
            if (currentLat && currentLon) {
                routingControl.setWaypoints([L.latLng(currentLat, currentLon), L.latLng(lat, lon)]);
                document.querySelector(".leaflet-routing-container").style.display = "flex";
                _currentTargetFid = fid;
                loadParkings();
                if (document.getElementById('metzToggle') && document.getElementById('metzToggle').checked) startMetzPolling(fid);
            } else {
                alert("Position de l'utilisateur inconnue.");
            }
        }

        /**
         * quitNavigation()
         * Interrompt le guidage et nettoie l'UI associée.
         */
        function quitNavigation() {
            try {
                // arrêter le polling et réinitialiser la cible car on quitte la nav
                stopMetzPolling(true);
                if (routingControl) {
                    routingControl.setWaypoints([]);
                }
                const routingEl = document.querySelector('.leaflet-routing-container');
                if (routingEl) routingEl.style.display = 'none';
                const mg = document.querySelector('.menuGuider');
                if (mg) mg.style.display = 'flex';
                loadParkings();
            } catch (e) {
                console.warn('quitNavigation', e);
            }
        }

        // Centrage simple sur la position utilisateur
        function centrerPosition() {
            if (currentLat && currentLon) {
                map.setView([currentLat, currentLon], 16);
            } else {
                alert("Position de l'utilisateur inconnue.");
            }
        }

        /**
         * findNearestParking()
         * Cherche le parking le plus adapté selon les paramètres (hauteur, électrique, PMR)
         * et la disponibilité (si l'option Metz est activée).
         * Retourne la Feature GeoJSON choisie ou null.
         */
        async function findNearestParking() {
            try {
                const res = await fetch('api/parkings_geojson.php');
                const data = await res.json();

                let candidates = data.features.map(feature => { const lat = feature.geometry.coordinates[1]; const lon = feature.geometry.coordinates[0]; const distance = getDistance(currentLat, currentLon, lat, lon); return { feature, distance }; }).filter(c => !isNaN(c.distance));

                try {
                    const heightVal = document.getElementById('heightMax')?.value || '';
                    const heightReq = heightVal !== '' ? Number(heightVal) : null;
                    const electricOnly = document.getElementById('electricToggle')?.checked || false;

                    candidates = candidates.filter(c => {
                        try {
                            const props = c.feature.properties || {};
                            if (heightReq != null && props.hauteur_max !== undefined && props.hauteur_max !== null && props.hauteur_max !== '') { const pMax = Number(props.hauteur_max); if (!isNaN(pMax) && pMax > 0 && heightReq > pMax) return false; }
                            if (electricOnly) { const pElec = props.electrique || props.electrique_capable || props.electrique_spots || null; if (!pElec) return false; }
                            if (pmr) { const pPmr = props.pmr || props.access_pmr || props.pmr_accessible || null; if (!pPmr) return false; }
                            return true;
                        } catch (e) { return true; }
                    });

                    if (!candidates.length) { document.getElementById("nearestParkingName").textContent = 'Aucun parking compatible avec ces contraintes'; return null; }
                } catch (e) { console.warn('apply param constraints failed', e); }

                candidates.sort((a, b) => a.distance - b.distance);

                const metzEnabled = document.getElementById('metzToggle')?.checked;
                if (!metzEnabled) { const nearest = candidates.length ? candidates[0].feature : null; document.getElementById("nearestParkingName").textContent = nearest ? (nearest.properties.lib || 'Parking inconnu') : 'Aucun parking trouvé'; return nearest; }

                for (const c of candidates) {
                    const feature = c.feature; const fid = feature.properties.fid || feature.properties.id || 0; if (!fid) continue; const info = await fetchMetzAvailabilityOnce(fid); if (info && info.value != null) { const num = Number(info.value); if (!Number.isNaN(num) && num > 0) { document.getElementById("nearestParkingName").textContent = feature.properties.lib || 'Parking disponible'; return feature; } } else { continue; }
                }

                document.getElementById("nearestParkingName").textContent = 'Aucun parking disponible (Metz)';
                return null;
            } catch (err) { console.error("Erreur lors du chargement des parkings :", err); document.getElementById("nearestParkingName").textContent = "Erreur de chargement"; }
        }

        // Démarrer le guidage vers le parking le plus proche
        async function goToNearestParking() {
            try {
                const nearest = await findNearestParking();
                if (!nearest) {
                    alert('Aucun parking trouvé pour ces critères.');
                    return;
                }
                const fid = nearest.properties?.fid || nearest.properties?.id || (nearest.id && !isNaN(parseInt(nearest.id)) ? parseInt(nearest.id) : null);
                goToParking(nearest.geometry.coordinates[1], nearest.geometry.coordinates[0], fid);
                const mg = document.querySelector('.menuGuider');
                if (mg) mg.style.display = 'none';
            } catch (e) { console.warn('goToNearestParking', e); }
        }

        /**
         * attemptAutoSwitchIfNeeded()
         * Si le parking ciblé devient plein, tente de basculer automatiquement
         * vers le suivant disponible (throttlé pour éviter oscillations).
         */
        async function attemptAutoSwitchIfNeeded() {
            try {
                const now = Date.now();
                if (now - _lastAutoSwitchTs < AUTO_SWITCH_TTL) return;
                _lastAutoSwitchTs = now;
                if (_currentTargetFid == null) return;
                const nearest = await findNearestParking();
                if (!nearest) return;
                const newFid = nearest.properties?.fid || nearest.properties?.id || (nearest.id && !isNaN(parseInt(nearest.id)) ? parseInt(nearest.id) : null);
                if (!newFid) return;
                if (Number(newFid) === Number(_currentTargetFid)) return;
                goToParking(nearest.geometry.coordinates[1], nearest.geometry.coordinates[0], newFid);
            } catch (e) { console.warn('attemptAutoSwitchIfNeeded', e); }
        }

        // onParamChange() : appelé à chaque changement de paramètres
        async function onParamChange() {
            try {
                saveParamSettings();
                await loadParkings();
                const nearest = await findNearestParking();
                if (_currentTargetFid != null && nearest && nearest.geometry && nearest.geometry.coordinates) {
                    const fid = nearest.properties?.fid || nearest.properties?.id || (nearest.id && !isNaN(parseInt(nearest.id)) ? parseInt(nearest.id) : null);
                    goToParking(nearest.geometry.coordinates[1], nearest.geometry.coordinates[0], fid);
                }
            } catch (e) { console.warn('onParamChange', e); }
        }

        // Attacher les écouteurs sur les contrôles de paramètres
        try { const elPmr = document.getElementById('pmrToggle'); const elHeight = document.getElementById('heightMax'); const elElec = document.getElementById('electricToggle'); if (elPmr) elPmr.addEventListener('change', () => { togglePMR(); onParamChange(); }); if (elHeight) elHeight.addEventListener('input', () => { onParamChange(); }); if (elHeight) elHeight.addEventListener('change', () => { onParamChange(); }); if (elElec) elElec.addEventListener('change', () => { onParamChange(); }); } catch (e) { console.warn('attach param listeners failed', e); }
    </script>
</body>

</html>
