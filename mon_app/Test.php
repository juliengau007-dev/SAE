<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Carte des parkings</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
  <style>
    #map { height: 100vh; width: 100%; }
  </style>
</head>
<body>
  <div id="map"></div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

  <script>
    let map = null;
    let userMarker = null;
    let currentLat = null;
    let currentLon = null;
    let routingControl = null;

    const rayonKm = 2; // Rayon de recherche

    // Fonction de calcul de distance
    function getDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // km
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(lat1 * Math.PI / 180) *
                Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) ** 2;
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    // Initialisation de la carte
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(position => {
        currentLat = position.coords.latitude;
        currentLon = position.coords.longitude;

        // Centrer la carte dès le départ sur la position actuelle
        map = L.map('map').setView([currentLat, currentLon], 15);

        // Ajouter le fond de carte
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Ajouter le contrôle d’itinéraire
        routingControl = L.Routing.control({
          waypoints: [],
          routeWhileDragging: false
        }).addTo(map);

        // Ajouter le marker rouge de l'utilisateur
        userMarker = L.circleMarker([currentLat, currentLon], {
          radius: 6,
          color: 'red'
        }).addTo(map)
          .bindPopup("Vous êtes ici")
          .openPopup();

        // Charger les parkings après initialisation
        loadParkings();

        // Mettre à jour en temps réel
        navigator.geolocation.watchPosition(updatePosition);

      }, err => {
        alert("Impossible de vous localiser : " + err.message);
      });
    } else {
      alert("La géolocalisation n'est pas supportée par ce navigateur.");
    }

    // Mettre à jour la position en temps réel
    function updatePosition(pos) {
      currentLat = pos.coords.latitude;
      currentLon = pos.coords.longitude;
      userMarker.setLatLng([currentLat, currentLon]);
    }

    // Charger et afficher les parkings depuis le backend PHP
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
              const nom = feature.properties.nom || "Parking";
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

    // Itinéraire vers un parking
    function goToParking(lat, lon) {
      if (currentLat && currentLon) {
        routingControl.setWaypoints([
          L.latLng(currentLat, currentLon),
          L.latLng(lat, lon)
        ]);
      } else {
        alert("Position de l'utilisateur inconnue.");
      }
    }
  </script>
</body>
</html>
