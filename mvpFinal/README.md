# V3 - Carte des parkings

Structure proposée et fichiers principaux:

utilisateur. obtenu depuis le WFS distant. du GeoJSON depuis le WFS.
`public/api/get_parkings.php`.

Comment exécuter:

1. Placez ce dossier dans votre serveur web local (ex: `c:\wamp64\www\V3`).
2. Ouvrez `http://localhost/V3/public/index.php` dans votre navigateur.

Remarques:

production, préférez cURL avec timeout et gestion d'erreurs. la compatibilité.

Node.js backend (optionnel)

Un serveur Node.js minimal a été ajouté dans `server/` pour exposer la même API
en JavaScript. Il utilise `mysql2` et `express`.

Installation et démarrage du serveur Node.js:

1. Ouvrez un terminal dans `c:\wamp64\www\V3\server`
2. Installez les dépendances:

```powershell
npm install
```

3. Lancez le serveur:

```powershell
npm start
```

Le serveur écoute par défaut sur le port `3000`. Endpoints:

- `GET /api/parkings` — liste des parkings
- `GET /api/parkings/:id` — détail d'un parking
- `GET /api/users` — liste des utilisateurs
- `GET /api/users/:id` — détail d'un utilisateur
- `POST /api/users` — créer un utilisateur (JSON body
  `{ nom, email, mdp, pmr, id_vehicule }`)

Remarques de sécurité:

- Les mots de passe sont maintenant hashés avec `bcryptjs` dans l'API Node.js.
- Toujours éviter d'utiliser l'utilisateur `root` sans mot de passe en
  production.
