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

            <div class="pmr-label">
                <span>PMR (mobilité réduite)</span>
                <label class="switch">
                    <input type="checkbox" id="pmrToggle" onchange="togglePMR()">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

    <script>
        let map, userMarker, currentLat, currentLon, routingControl;
        let email = "", password = "", pmr = false;
        const rayonKm = 50;

        // ---- MENU PARAM ----
        document.getElementById("parametre").addEventListener("click", () => {
            document.getElementById("menuParam").style.display = "flex";
        });

        function fermerMenu() {
            document.getElementById("menuParam").style.display = "none";
        }

        function connexion() {
            email = document.getElementById("email").value;
            password = document.getElementById("password").value;
            alert(`Connexion : ${email}`);
        }

        function inscription() {
            email = document.getElementById("email").value;
            password = document.getElementById("password").value;
            alert(`Inscription : ${email}`);
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
            fetch('get_parkings.php')
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
    </script>
</body>

</html>
