# Projet MVP - API Parkings (GeoJSON)

## But

Ce dépôt fournit un petit service web qui expose des parkings au format GeoJSON.
Le projet charge les géométries depuis un WFS distant (eurometropolemetz),
enrichit optionnellement les entités avec des attributs stockés en base de
données, et présente une interface cartographique (Leaflet) pour guider
l'utilisateur vers un parking.

## Prérequis

- Windows + WAMP (Apache + MySQL + PHP) installé. Le projet est prévu pour être
  placé dans `c:\wamp64\www\MVP`.
- PHP activé (extensions PDO/MySQL disponibles).

## Installation & mise en route (base de données + site)

1. Copier le dossier du projet dans le répertoire web de WAMP :

   - `c:\wamp64\www\MVP`

2. Démarrer WAMP (Apache et MySQL) depuis le panneau WAMP.

3. Importer le schéma et les données fournies (fichier SQL).

4. Configurer la connexion à la BDD utilisée par l'API :

   - Fichier procédural : `public/api/_db_connect.php` — modifiez les variables
     `$dbHost`, `$dbName`, `$dbUser`, `$dbPass` pour pointer vers votre instance
     locale (exemple pour WAMP local) :

```php
$dbHost = '127.0.0.1';
$dbName = 'sae_parking';
$dbUser = 'root';
$dbPass = '';
```

    - Classe OOP : `src/Database/Database.php` a des valeurs par défaut (host `127.0.0.1`, db `ParkingMetz`, user `root`).
     Si vous préférez l'utiliser, adaptez le paramètre `$db` dans le constructeur ou instanciez-la avec vos valeurs.

5. Accéder au site et à l'API :

   - Interface principale (cartographie) :
     `http://localhost/MVP/public/index.php`
   - Page de test (tableau JSON) :
     `http://localhost/MVP/public/test_parking.html`
   - Endpoint principal (GeoJSON via service distant) :
     `http://localhost/MVP/public/api/get_parkings.php`
   - Endpoint local enrichi / cache :
     `http://localhost/MVP/public/api/parkings_geojson.php`

## Guide utilisateur (interface web)

Page principale : `public/index.php` (carte interactive, guidage et paramètres)

- Cartographie : carte Leaflet affichant les parkings (marqueurs) et la position
  utilisateur.

- Bouton `📍` (id `btnCentrer`) — recentre la carte sur votre position actuelle
  (nécessite autorisation de géolocalisation).

- Bandeau de guidage (bas-centre) :
  - Affiche le nom du parking le plus proche compatible avec vos filtres.
  - Bouton `Allez` (id `btnGuider`) — lance le guidage vers le parking
    sélectionné (calcule un itinéraire et ouvre le panneau de routage).

- Popup d'un parking (cliquer sur un marqueur) :
  - Contient un bouton `➡️` qui lance l'itinéraire vers ce parking précis
    (`goToParking(lat,lon,fid)`).

- Panneau de routage (Leaflet Routing Machine) :
  - Affiche l'itinéraire étape par étape.
  - Bouton `✖` (dans le panneau) — quitte la navigation (fonction
    `quitNavigation()`), stoppe le polling Metz et réaffiche la liste des
    parkings.

- Bouton `⚙️` (id `parametre`) — ouvre le panneau de paramètres (`#menuParam`) :
  - PMR (toggle) — filtre les parkings accessibles PMR.
  - Hauteur max (input number) — affiche uniquement les parkings compatibles
    avec la hauteur de votre véhicule.
  - Véhicules électriques (toggle) — filtre pour parkings offrant des bornes
    électriques (si info disponible).
  - Vérifier disponibilité Metz (toggle `metzToggle`) — si activé, l'application
    tente d'obtenir les places disponibles via les données locales ou via une
    URL externe configurée (optionnelle).

- Paramètres persistants : les préférences (PMR, hauteur, électrique, URL Metz)
  sont sauvegardées dans `localStorage`.

## Comportement côté client

- Chargement des données : la page charge `api/parkings_geojson.php` (GeoJSON
  enrichi). Le script applique un rayon d'intérêt (par défaut 50 km) autour de
  l'utilisateur pour limiter les données affichées.
- Filtrage : les paramètres (PMR, hauteur, électrique) sont appliqués côté
  client avant d'afficher les parkings.
- Vérification de disponibilité : si l'option Metz est activée, le client tente
  d'extraire une valeur de disponibilité depuis la feature (propriété connue) ou
  depuis une URL externe (si configurée). Le polling est périodique.

## Page de test

- `public/test_parking.html` : page simple qui appelle
  `api/parkings_geojson.php` et affiche les résultats dans un tableau HTML
  (utile pour debug / vérifier les propriétés disponibles dans le GeoJSON).

## Endpoints et rôle de chaque fichier API

- `public/api/get_parkings.php` : wrapper minimal qui appelle
  `src/Service/ParkingService.php` pour récupérer le GeoJSON directement depuis
  le WFS distant et le renvoyer au client.
- `public/api/parkings_geojson.php` : récupère le GeoJSON (via `ParkingService`
  si besoin), met en cache serveur (`parkings_cache.json`), puis enrichit chaque
  feature avec les attributs issus de la table `parking` (via
  `_db_connect.php`).
- `public/api/_db_connect.php` : connexion PDO rapide (procédural). Les scripts
  `parkings_geojson.php` l'incluent si présent.

## Dépannage courant

- Erreur 500 depuis `_db_connect.php` : vérifier que MySQL est démarré et que
  les identifiants dans `_db_connect.php` sont corrects. Vérifier aussi que
  l'extension PDO MySQL est activée dans PHP.
- Erreur 502 depuis `get_parkings.php` ou `parkings_geojson.php` : le service
  WFS distant est indisponible ou la requête a expiré. `parkings_geojson.php`
  essaie d'utiliser `parkings_cache.json` en fallback si disponible.
- Carte vide / pas de géolocalisation : accepter la demande d'autorisation du
  navigateur; sinon la carte tente de fonctionner (mais certaines
  fonctionnalités nécessitent la position).
