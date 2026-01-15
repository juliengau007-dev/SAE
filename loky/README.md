# Loky - Application de Recherche de Parkings

## Description

Loky est une application web interactive de recherche et de guidage vers des
parkings disponibles dans plusieurs villes européennes. L'application permet aux
utilisateurs de trouver des parkings compatibles avec leurs besoins (véhicules
électriques, PMR, hauteur maximale, etc.), de se faire guider vers le parking le
plus proche, et de gérer leurs véhicules et parkings favoris.

### Fonctionnalités principales

- **Recherche multi-villes** : Support pour Metz, Strasbourg et Londres
- **Carte interactive** : Affichage des parkings sur une carte Leaflet avec
  géolocalisation
- **Guidage en temps réel** : Itinéraires vers les parkings sélectionnés
- **Filtres personnalisés** : PMR, hauteur maximale, véhicules électriques,
  gratuit uniquement
- **Mode déplacement virtuel** : Simulation de déplacement pour tester
  l'application
- **Gestion des comptes utilisateurs** : Inscription, connexion, gestion des
  véhicules
- **Parkings enregistrés** : Sauvegarde des parkings favoris
- **Historique des trajets** : Suivi des parkings utilisés
- **Multilingue** : Support français et anglais
- **Mode hors ligne** : Cache des données pour utilisation sans connexion

## Prérequis

Avant d'installer et d'utiliser Loky, assurez-vous d'avoir :

- **Serveur web** : Apache (recommandé avec WAMP sur Windows)
- **Base de données** : MySQL ou MariaDB
- **PHP** : Version 7.4 ou supérieure avec les extensions suivantes activées :
  - PDO
  - PDO MySQL
  - JSON
  - cURL (pour les appels API externes)
- **Navigateur web** : Chrome, Firefox, Safari ou Edge (avec géolocalisation
  activée)
- **Connexion internet** : Pour charger les données des parkings et les cartes

## Installation

### 1. Téléchargement et placement des fichiers

1. Téléchargez ou clonez le dépôt du projet.
2. Placez le dossier du projet dans le répertoire web de votre serveur (par
   exemple `c:\wamp64\www\loky` pour WAMP).

### 2. Configuration de la base de données

1. Créez une nouvelle base de données MySQL nommée `ParkingMetz` (ou un autre
   nom de votre choix).
2. Importez le schéma de base de données fourni dans le fichier `promptbdd.txt`
   :
   - Ouvrez phpMyAdmin ou votre outil MySQL préféré
   - Exécutez les requêtes SQL du fichier `promptbdd.txt`
3. Les données d'exemple pour les parkings de Metz sont incluses dans le script
   SQL.

### 3. Configuration de l'application

Modifiez les fichiers de configuration suivants :

#### Fichier `public/api/_db_connect.php`

```php
<?php
$dbHost = '127.0.0.1';  // Adresse de votre serveur MySQL
$dbName = 'ParkingMetz';  // Nom de votre base de données
$dbUser = 'root';  // Votre nom d'utilisateur MySQL
$dbPass = '';  // Votre mot de passe MySQL
?>
```

#### Fichier `src/Database/Database.php` (optionnel, si vous utilisez la classe OOP)

```php
<?php
class Database {
    private $host = '127.0.0.1';
    private $db_name = 'ParkingMetz';
    private $username = 'root';
    private $password = '';
    // ...
}
?>
```

### 4. Démarrage du serveur

1. Démarrez votre serveur web (Apache) et MySQL.
2. Accédez à l'application via votre navigateur :
   `http://localhost/loky/public/index.html`

## Guide d'utilisation

### Pour les nouveaux utilisateurs

#### Première visite

1. **Autorisation de géolocalisation** : Lors de votre première visite,
   l'application vous demandera l'autorisation d'accéder à votre position.
   Cliquez sur "Autoriser" pour une expérience optimale.

2. **Sélection de la langue** : Utilisez le menu paramètres (⚙️) pour changer la
   langue entre français et anglais.

3. **Exploration de la carte** : La carte affiche automatiquement les parkings
   autour de votre position actuelle.

#### Recherche d'un parking

1. **Filtres de base** (sans compte) :
   - **PMR** : Cochez pour afficher uniquement les parkings accessibles aux
     personnes à mobilité réduite
   - **Hauteur maximale** : Entrez la hauteur de votre véhicule en cm
   - **Véhicules électriques** : Cochez pour voir les parkings avec bornes de
     recharge
   - **Gratuit uniquement** : Cochez pour masquer les parkings payants

2. **Guidage vers le parking le plus proche** :
   - Cliquez sur le bouton "Allez" dans le bandeau inférieur pour vous diriger
     vers le parking recommandé
   - Suivez les instructions de l'itinéraire affiché

3. **Sélection manuelle** :
   - Cliquez sur un marqueur de parking sur la carte
   - Dans la popup, cliquez sur "➡️" pour obtenir l'itinéraire

#### Mode déplacement virtuel

Pour tester l'application sans vous déplacer :

1. Activez le mode virtuel via le menu paramètres
2. Utilisez la croix directionnelle pour simuler vos déplacements
3. Ajustez la vitesse avec le curseur
4. Définissez votre "maison" et naviguez-y

### Création d'un compte utilisateur

Pour accéder à des fonctionnalités avancées :

1. Cliquez sur le bouton paramètres (⚙️)
2. Cliquez sur "Inscription"
3. Remplissez le formulaire :
   - Email
   - Mot de passe
   - Nom (optionnel)
4. Validez votre inscription

#### Connexion

1. Dans le menu paramètres, cliquez sur "Connexion"
2. Entrez votre email et mot de passe

### Gestion des véhicules (utilisateurs connectés)

1. Dans les paramètres, section "Mes véhicules"
2. Cliquez sur "+" pour ajouter un véhicule
3. Entrez :
   - Plaque d'immatriculation (optionnel)
   - Hauteur en cm
   - Type : Voiture, Vélo électrique, etc.

L'application utilisera automatiquement les caractéristiques de votre véhicule
pour filtrer les parkings.

### Parkings enregistrés

1. Cliquez sur un marqueur de parking
2. Dans la popup, cliquez sur "💾" pour enregistrer le parking
3. Accédez à vos parkings enregistrés via le menu paramètres

### Changement de ville

L'application détecte automatiquement votre ville, mais vous pouvez la changer :

1. Utilisez le sélecteur de ville dans l'interface (si disponible)
2. Ou modifiez manuellement l'URL : `?city=metz`, `?city=strasbourg`, ou
   `?city=london`

## API

L'application expose plusieurs endpoints API :

- `api/parkings_geojson.php` : Retourne les parkings au format GeoJSON
  - Paramètres : `city` (metz, strasbourg, london)
- `api/get_parkings.php` : Endpoint alternatif pour récupérer les données
- `api/Parking/index.php` : Gestion CRUD des parkings (nécessite
  authentification)
- `api/Utilisateur/index.php` : Gestion des utilisateurs
- `api/Vehicule/index.php` : Gestion des véhicules
- `api/SavedParkings/index.php` : Gestion des parkings enregistrés

## Dépannage

### Problèmes courants

1. **Carte ne s'affiche pas** :
   - Vérifiez votre connexion internet
   - Autorisez la géolocalisation dans votre navigateur

2. **Aucun parking affiché** :
   - Vérifiez les filtres appliqués
   - Essayez de zoomer ou de vous déplacer sur la carte

3. **Erreur de base de données** :
   - Vérifiez la configuration dans `_db_connect.php`
   - Assurez-vous que MySQL est démarré

4. **Mode virtuel ne fonctionne pas** :
   - Actualisez la page
   - Vérifiez que JavaScript est activé

### Logs et débogage

- Ouvrez la console développeur de votre navigateur (F12) pour voir les erreurs
  JavaScript
- Vérifiez les logs du serveur web pour les erreurs PHP

## Développement

### Structure du projet

```
loky/
├── public/           # Fichiers publics accessibles via web
│   ├── index.html    # Page principale
│   ├── api/          # Endpoints API
│   ├── css/          # Styles CSS
│   ├── js/           # Scripts JavaScript
│   └── i18n/         # Fichiers de traduction
├── src/              # Code source PHP
│   ├── Database/     # Classes de base de données
│   └── Service/      # Services métier
├── promptbdd.txt     # Schéma de base de données
└── README.md         # Ce fichier
```

### Technologies utilisées

- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Cartes** : Leaflet.js
- **Itinéraires** : Leaflet Routing Machine
- **Backend** : PHP 7.4+
- **Base de données** : MySQL
- **Internationalisation** : JSON-based

## Contribution

Pour contribuer au développement :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité
3. Commitez vos changements
4. Poussez vers votre fork
5. Créez une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## Support

Pour obtenir de l'aide :

- Consultez la documentation
- Ouvrez une issue sur GitHub
- Contactez l'équipe de développement

---

_Dernière mise à jour : Janvier 2026_ depuis une URL externe (si configurée). Le
polling est périodique.

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
