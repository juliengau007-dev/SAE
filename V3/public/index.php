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

        /* Vehicle section layout */
        #vehiculeSection {
            border-top: 1px solid #eee;
            padding-top: 8px;
        }

        #myVehicleContainer {
            background: #fafafa;
            border: 1px solid #eee;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .veh-form {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .veh-form label {
            display: block;
            flex: 1 1 45%;
            min-width: 140px;
        }

        .veh-form input[type="text"],
        .veh-form input[type="number"] {
            width: 100%;
            box-sizing: border-box;
        }

        #vehiculeSection h4 {
            margin: 6px 0 4px 0;
        }

        #vehiculeSection .veh-btn {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 10px;
            border-radius: 4px;
        }

        #vehiculeSection .veh-btn:hover {
            background: #218838;

            #vehiculeSection .veh-del {
                background: #dc3545;
                color: #fff;
                border: none;
                padding: 8px 10px;
                border-radius: 4px;
            }

            #vehiculeSection .veh-del:hover {
                background: #c82333;
            }
        }

        /* Logout link shown when user is connected */
        #menuParam .logout-link {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: yellow;
            /* highlighted */
            color: #000;
            /* black text */
            padding: 2px 6px;
            border-radius: 2px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            box-shadow: none;
            display: inline-block;
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

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #aaa;
            border-radius: 5px;
        }

        button.connexion {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px;
            cursor: pointer;
        }

        button.connexion:hover {
            background-color: #0056b3;
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

            <h3>Connexion</h3>
            <input type="email" id="email" placeholder="Email">
            <input type="password" id="password" placeholder="Mot de passe">
            <button class="connexion" onclick="connexion()">Se connecter</button>
            <button class="connexion" onclick="inscription()">S’inscrire</button>

            <!-- Mes véhicules (visible quand connecté) -->
            <div id="vehiculeSection" style="display:none;margin-top:6px;">
                <h3>Mes véhicules</h3>
                <div id="myVehicleContainer">Aucun véhicule associé.</div>

                <h4>Ajouter un véhicule</h4>
                <div class="veh-form">
                    <label>
                        Plaque d'immatriculation
                        <input type="text" id="veh_plaque" placeholder="AA-123-BB">
                    </label>
                    <label>
                        Hauteur (cm)
                        <input type="number" id="veh_hauteur" placeholder="150">
                    </label>
                    <div style="display:flex;align-items:center;gap:8px;flex:1 1 100%;">
                        <label style="margin-right:8px;"><input type="checkbox" id="veh_electrique"> Électrique</label>
                        <label><input type="checkbox" id="veh_velo"> Vélo</label>
                        <button id="veh_add_btn" class="veh-btn" onclick="addVehicle()"
                            style="margin-left:auto;">Ajouter</button>
                    </div>
                </div>
            </div>

            <div class="pmr-label">
                <span>PMR (mobilité réduite)</span>
                <label class="switch">
                    <input type="checkbox" id="pmrToggle" onchange="togglePMR()">
                    <span class="slider"></sp an>
                </label>
            </div>

            <div id="loggedInInfo" style="display:none;">
                <div id="connectedAs" style="margin-bottom:6px;font-weight:600;"></div>
                <a href="#" id="logoutLink" class="logout-link" onclick="logout(); return false;">Se déconnecter</a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

    <script>
        let map, userMarker, currentLat, currentLon, routingControl;
        let currentUser = null;
        let editingVehicleId = null;
        let email = "", password = "", pmr = false;
        const rayonKm = 50;

        // ---- MENU PARAM ----
        document.getElementById("parametre").addEventListener("click", () => {
            document.getElementById("menuParam").style.display = "flex";
            updateMenuForUser();
        });

        function fermerMenu() {
            document.getElementById("menuParam").style.display = "none";
        }

        async function connexion() {
            email = document.getElementById("email").value;
            password = document.getElementById("password").value;
            try {
                const res = await fetch('api/Utilisateur/index.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email, mdp: password })
                });
                const json = await res.json();
                if (!res.ok || !json.ok) {
                    // clear only password on failed login
                    document.getElementById('password').value = '';
                    alert('Échec connexion : ' + (json.error || res.status));
                    return;
                }
                currentUser = json.user;
                // reflect PMR setting in UI
                if (typeof currentUser.pmr !== 'undefined') {
                    document.getElementById('pmrToggle').checked = (Number(currentUser.pmr) === 1 || currentUser.pmr === true);
                    pmr = document.getElementById('pmrToggle').checked;
                }
                // clear input fields on successful login
                document.getElementById('email').value = '';
                document.getElementById('password').value = '';
                updateMenuForUser();
                alert('Connecté en tant que ' + (currentUser.nom || currentUser.email));
            } catch (err) {
                console.error(err);
                alert('Erreur connexion : ' + err.message);
            }
        }

        async function inscription() {
            const nom = document.getElementById("email").value.split('@')[0] || null;
            const emailVal = document.getElementById("email").value;
            const passwordVal = document.getElementById("password").value;
            const pmrVal = document.getElementById('pmrToggle').checked ? 1 : 0;
            if (!emailVal || !passwordVal) { alert('Veuillez fournir un email et mot de passe'); return; }
            try {
                const res = await fetch('api/Utilisateur/index.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nom: nom, email: emailVal, mdp: passwordVal, pmr: pmrVal })
                });
                const json = await res.json();
                if (!res.ok || !json.ok) {
                    alert('Échec inscription : ' + (json.error || res.status));
                    return;
                }
                // Automatically attempt to log the user in after successful registration
                try {
                    await connexion();
                } catch (e) {
                    // fallback message
                    alert('Inscription réussie, id=' + json.id + '. Vous pouvez maintenant vous connecter.');
                }
            } catch (err) {
                console.error(err);
                alert('Erreur inscription : ' + err.message);
            }
        }

        function updateMenuForUser() {
            const loggedBlock = document.getElementById('loggedInInfo');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const connButtons = document.querySelectorAll('button.connexion');
            const connectedAs = document.getElementById('connectedAs');
            const vehSection = document.getElementById('vehiculeSection');
            const menuParam = document.getElementById('menuParam');

            if (currentUser) {
                // hide login inputs/buttons
                emailInput.style.display = 'none';
                passwordInput.style.display = 'none';
                connButtons.forEach(b => b.style.display = 'none');

                // show vehicle section and enlarge menu
                if (vehSection) vehSection.style.display = 'block';
                if (menuParam) menuParam.classList.add('connected');

                connectedAs.textContent = 'Connecté: ' + (currentUser.nom || currentUser.email || 'utilisateur');
                loggedBlock.style.display = 'block';
                // load vehicle info for connected user
                loadUserVehicle();
            } else {
                // show login inputs/buttons
                emailInput.style.display = '';
                passwordInput.style.display = '';
                connButtons.forEach(b => b.style.display = 'inline-block');

                connectedAs.textContent = '';
                loggedBlock.style.display = 'none';
                if (vehSection) vehSection.style.display = 'none';
                if (menuParam) menuParam.classList.remove('connected');
            }
        }

        function logout() {
            currentUser = null;
            document.getElementById('pmrToggle').checked = false;
            pmr = false;
            updateMenuForUser();
            alert('Vous êtes déconnecté.');
        }

        function togglePMR() {
            pmr = document.getElementById("pmrToggle").checked;
            console.log("PMR =", pmr);
        }

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
            navigator.geolocation.getCurrentPosition(position => {
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

                routingControl = L.Routing.control({
                    waypoints: [],
                    routeWhileDragging: false,
                    show: true,
                    lineOptions: {
                        styles: [{ color: 'blue', opacity: 0.6, weight: 3 }]
                    }
                }).addTo(map);

                // Déplacer le panneau Leaflet dans notre conteneur flexbox
                const routingContainer = document.querySelector('.leaflet-routing-container');
                document.getElementById('guidage').appendChild(routingContainer);

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
                navigator.geolocation.watchPosition(updatePosition);
            }, err => {
                alert("Impossible de vous localiser : " + err.message);
            });
        } else {
            alert("La géolocalisation n'est pas supportée par ce navigateur.");
        }

        function updatePosition(pos) {
            currentLat = pos.coords.latitude;
            currentLon = pos.coords.longitude;
            if (userMarker) userMarker.setLatLng([currentLat, currentLon]);
        }

        function loadParkings() {
            fetch('api/parkings_geojson.php')
                .then(res => res.json())
                .then(data => {
                    L.geoJSON(data, {
                        filter: feature => {
                            const lat = feature.geometry.coordinates[1];
                            const lon = feature.geometry.coordinates[0];
                            return getDistance(currentLat, currentLon, lat, lon) <= rayonKm;
                        },
                        onEachFeature: (feature, layer) => {
                            const nom = feature.properties.lib || "Parking";
                            layer.bindPopup(`
                <b>${nom}</b><br>
                <button onclick="goToParking(${feature.geometry.coordinates[1]}, ${feature.geometry.coordinates[0]})">
                  Itinéraire
                </button>
              `);
                        }
                    }).addTo(map);
                })
                .catch(err => console.error("Erreur lors du chargement des parkings :", err));
        }

        function goToParking(lat, lon) {
            if (currentLat && currentLon) {
                routingControl.setWaypoints([
                    L.latLng(currentLat, currentLon),
                    L.latLng(lat, lon)
                ]);
                document.querySelector(".leaflet-routing-container").style.display = "flex";
            } else {
                alert("Position de l'utilisateur inconnue.");
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
                const res = await fetch('api/parkings_geojson.php');
                const data = await res.json();

                let nearestParking = null;
                let minDistance = Infinity;

                data.features.forEach(feature => {
                    const lat = feature.geometry.coordinates[1];
                    const lon = feature.geometry.coordinates[0];
                    const distance = getDistance(currentLat, currentLon, lat, lon);

                    if (distance < minDistance) {
                        minDistance = distance;
                        nearestParking = feature;
                    }
                });

                if (nearestParking) {
                    const nom = nearestParking.properties.lib || "Parking inconnu";
                    document.getElementById("nearestParkingName").textContent = nom;
                } else {
                    document.getElementById("nearestParkingName").textContent = "Aucun parking trouvé";
                }

                return nearestParking;
            } catch (err) {
                console.error("Erreur lors du chargement des parkings :", err);
                document.getElementById("nearestParkingName").textContent = "Erreur de chargement";
            }
        }

        function goToNearestParking() {
            findNearestParking().then(nearestParking => {
                if (nearestParking) {
                    document.querySelector(".menuGuider").style.display = "none";
                    const lat = nearestParking.geometry.coordinates[1];
                    const lon = nearestParking.geometry.coordinates[0];
                    goToParking(lat, lon);
                } else {
                    alert("Aucun parking trouvé.");
                }
            })
                .catch(err => console.error("Erreur lors de la recherche du parking le plus proche :", err));
        }

        function fermerGuidageAuto() {
            routingControl.setWaypoints([]);
            document.querySelector(".menuGuider").style.display = "none";
        }

        // ----- Vehicles management -----
        async function loadUserVehicle() {
            const container = document.getElementById('myVehicleContainer');
            if (!currentUser || !currentUser.id_utilisateur) {
                container.innerHTML = 'Aucun véhicule associé.';
                return;
            }
            const vehId = currentUser.id_vehicule;
            if (!vehId) {
                container.innerHTML = 'Aucun véhicule associé.';
                return;
            }
            try {
                const res = await fetch(`api/Vehicule/index.php?action=get&id=${vehId}`);
                if (!res.ok) {
                    container.innerHTML = 'Erreur lors du chargement du véhicule.';
                    return;
                }
                const data = await res.json();
                container.innerHTML = `
                    <div>
                      <div><b>Plaque:</b> ${data.plaque_immatriculation || '-'}<br><b>Hauteur:</b> ${data.hauteur || '-'} cm</div>
                      <div style="margin-top:8px;display:flex;gap:8px;">
                        <button class="veh-btn" onclick="startEditVehicle(${data.id_vehicule})">Modifier</button>
                        <button class="veh-del" onclick="deleteVehicle(${data.id_vehicule})">Supprimer</button>
                      </div>
                    </div>`;
            } catch (err) {
                console.error(err);
                container.innerHTML = 'Erreur lors du chargement du véhicule.';
            }
        }

        function startEditVehicle(id) {
            editingVehicleId = id;
            // fetch vehicle to populate form
            fetch(`api/Vehicule/index.php?action=get&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('veh_plaque').value = data.plaque_immatriculation || '';
                    document.getElementById('veh_hauteur').value = data.hauteur || '';
                    // set checkboxes to defaults (we don't know elekt/velo flags here)
                    document.getElementById('veh_electrique').checked = false;
                    document.getElementById('veh_velo').checked = false;
                    const addBtn = document.getElementById('veh_add_btn');
                    if (addBtn) { addBtn.textContent = 'Modifier'; }
                    // open menu to show form
                    document.getElementById('menuParam').style.display = 'flex';
                    updateMenuForUser();
                })
                .catch(err => {
                    console.error(err);
                    alert('Erreur chargement véhicule pour modification.');
                });
        }

        async function deleteVehicle(id) {
            if (!confirm('Confirmez-vous la suppression de ce véhicule ?')) return;
            try {
                const res = await fetch(`api/Vehicule/index.php?action=delete&id=${id}`);
                const json = await res.json();
                if (!res.ok || !json.ok) {
                    alert('Erreur suppression véhicule');
                    return;
                }
                // Unlink from user
                try {
                    const upd = await fetch(`api/Utilisateur/index.php?action=update&id=${currentUser.id_utilisateur}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_vehicule: null })
                    });
                    const upj = await upd.json();
                    // update local
                    currentUser.id_vehicule = null;
                    loadUserVehicle();
                    alert('Véhicule supprimé et détaché de votre compte.');
                } catch (e) {
                    console.error(e);
                    alert('Véhicule supprimé mais erreur lors de la mise à jour utilisateur.');
                }
            } catch (err) {
                console.error(err);
                alert('Erreur suppression véhicule: ' + err.message);
            }
        }

        async function addVehicle() {
            if (!currentUser || !currentUser.id_utilisateur) {
                alert('Vous devez être connecté pour ajouter un véhicule.');
                return;
            }
            const plaque = document.getElementById('veh_plaque').value.trim();
            const hauteurRaw = document.getElementById('veh_hauteur').value;
            const hauteur = hauteurRaw ? parseInt(hauteurRaw, 10) : null;
            const electrique = document.getElementById('veh_electrique').checked;
            const velo = document.getElementById('veh_velo').checked;

            // if velo, plaque/hauteur optional
            if (!velo && !plaque) {
                alert('Veuillez renseigner la plaque d\'immatriculation (ou cocher Vélo).');
                return;
            }

            const payload = {};
            if (plaque) payload.plaque_immatriculation = plaque;
            if (hauteur) payload.hauteur = hauteur;

            const addBtn = document.getElementById('veh_add_btn');
            if (addBtn) { addBtn.disabled = true; addBtn.textContent = editingVehicleId ? 'Enregistrement...' : 'Ajout...'; }
            try {
                // If editing, call update instead
                if (editingVehicleId) {
                    const upd = await fetch(`api/Vehicule/index.php?action=update&id=${editingVehicleId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const upj = await upd.json();
                    if (!upd.ok || !upj.ok) {
                        alert('Erreur mise à jour véhicule: ' + (upj.error || upd.status));
                        if (addBtn) { addBtn.disabled = false; addBtn.textContent = 'Modifier'; }
                        return;
                    }
                    // finish edit
                    editingVehicleId = null;
                    if (addBtn) addBtn.textContent = 'Ajouter';
                    // clear form
                    document.getElementById('veh_plaque').value = '';
                    document.getElementById('veh_hauteur').value = '';
                    document.getElementById('veh_electrique').checked = false;
                    document.getElementById('veh_velo').checked = false;
                    loadUserVehicle();
                    alert('Véhicule mis à jour.');
                    if (addBtn) addBtn.disabled = false;
                    return;
                }

                const res = await fetch('api/Vehicule/index.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (!res.ok || !json.ok) {
                    alert('Erreur création véhicule: ' + (json.error || res.status));
                    if (addBtn) { addBtn.disabled = false; addBtn.textContent = 'Ajouter'; }
                    return;
                }
                const newId = json.id;

                // if velo selected, create entry in Velo table
                if (velo) {
                    try {
                        await fetch('api/Velo/index.php?action=create', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id_vehicule: newId })
                        });
                    } catch (e) { console.warn('Velo API error', e); }
                }
                // if electrique selected, create entry in Electrique table
                if (electrique) {
                    try {
                        await fetch('api/Electrique/index.php?action=create', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id_vehicule: newId })
                        });
                    } catch (e) { console.warn('Electrique API error', e); }
                }

                // Link vehicle to current user
                try {
                    const upd = await fetch(`api/Utilisateur/index.php?action=update&id=${currentUser.id_utilisateur}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_vehicule: newId })
                    });
                    const upj = await upd.json();
                    if (!upd.ok || !upj.ok) {
                        alert('Véhicule créé (id=' + newId + ') mais échec liaison utilisateur.');
                    } else {
                        // update local user and UI
                        currentUser.id_vehicule = newId;
                        // clear form
                        document.getElementById('veh_plaque').value = '';
                        document.getElementById('veh_hauteur').value = '';
                        document.getElementById('veh_electrique').checked = false;
                        document.getElementById('veh_velo').checked = false;
                        loadUserVehicle();
                        alert('Véhicule ajouté et lié à votre compte.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Véhicule créé mais erreur lors de la liaison utilisateur.');
                }
                if (addBtn) { addBtn.disabled = false; addBtn.textContent = 'Ajouter'; }
            } catch (err) {
                console.error(err);
                alert('Erreur création véhicule: ' + err.message);
                const addBtn2 = document.getElementById('veh_add_btn');
                if (addBtn2) { addBtn2.disabled = false; addBtn2.textContent = 'Ajouter'; }
            }
        }

        // toggle plaque/hauteur when 'Vélo' is checked
        (function initVehFormToggles() {
            const veloCb = document.getElementById('veh_velo');
            const plaque = document.getElementById('veh_plaque');
            const hauteur = document.getElementById('veh_hauteur');
            if (!veloCb) return;
            function update() {
                if (veloCb.checked) {
                    plaque.disabled = true;
                    plaque.style.opacity = '0.6';
                    hauteur.disabled = true;
                    hauteur.style.opacity = '0.6';
                } else {
                    plaque.disabled = false;
                    plaque.style.opacity = '';
                    hauteur.disabled = false;
                    hauteur.style.opacity = '';
                }
            }
            veloCb.addEventListener('change', update);
            // run once
            update();
        })();
    </script>
</body>

</html>