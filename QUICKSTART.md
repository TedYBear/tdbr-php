# TDBR Symfony - Démarrage Rapide

Guide ultra-rapide pour démarrer l'API Symfony TDBR.

## Installation Express (5 minutes)

### 1. Installer l'extension MongoDB PHP

**Windows (XAMPP/WAMP) :**

1. Télécharger : https://pecl.php.net/package/mongodb (version PHP 8.1 TS x64)
2. Copier `php_mongodb.dll` dans `C:\xampp\php\ext\`
3. Éditer `C:\xampp\php\php.ini`, ajouter :
   ```ini
   extension=mongodb
   ```
4. Redémarrer Apache

Vérifier :
```bash
php -m | grep mongodb
```

### 2. Installer les dépendances

```bash
cd C:\Users\Manu\Documents\TDBR\site_v3

# Désactiver SSL si nécessaire
composer config secure-http false
composer config disable-tls true

# Installer
composer install
```

Si erreur pour `mongodb/mongodb` ou `firebase/php-jwt`, l'installer manuellement :
```bash
composer require mongodb/mongodb firebase/php-jwt --no-scripts
```

### 3. Configuration

Le fichier `.env.local` est déjà configuré avec :
- MongoDB URI : `mongodb+srv://tdbr_db_user:...@tdbr.x5g60ng.mongodb.net/`
- Base : `tdbr`
- JWT Secret : `super_secret_key_to_change_in_production`

**Aucune modification nécessaire !**

### 4. Démarrer le serveur

```bash
# Option 1 : Serveur PHP intégré
php -S localhost:8000 -t public

# Option 2 : Symfony CLI (si installé)
symfony server:start
```

### 5. Tester

```bash
curl http://localhost:8000/api/health
```

✅ Devrait retourner :
```json
{
  "status": "OK",
  "database": {
    "mongodb": "connected"
  }
}
```

## Test rapide de l'API

### Créer un admin

```bash
curl -X POST http://localhost:8000/api/auth/inscription \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tdbr.fr","password":"admin123","prenom":"Admin","nom":"TDBR"}'
```

Retourne un `token`. Copier ce token.

### Se connecter

```bash
curl -X POST http://localhost:8000/api/auth/connexion \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tdbr.fr","password":"admin123"}'
```

### Créer une catégorie

```bash
curl -X POST http://localhost:8000/api/categories/admin \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -d '{"nom":"Test","slug":"test","description":"Catégorie de test","actif":true}'
```

### Lister les catégories (public)

```bash
curl http://localhost:8000/api/categories
```

## Points d'entrée API

### Publics (sans auth)

- `GET /api/health` - Health check
- `POST /api/auth/inscription` - Créer un compte
- `POST /api/auth/connexion` - Se connecter
- `GET /api/categories` - Liste catégories
- `GET /api/articles` - Liste articles
- `GET /api/collections` - Liste collections

### Authentifié (Bearer token)

- `GET /api/auth/profil` - Mon profil
- `PUT /api/auth/profil` - Modifier profil

### Admin (Bearer token + role=admin)

- `GET /api/categories/admin/all` - Toutes catégories
- `POST /api/categories/admin` - Créer catégorie
- `PUT /api/categories/admin/{id}` - Modifier
- `DELETE /api/categories/admin/{id}` - Supprimer

(Idem pour articles, collections)

- `POST /api/uploads/image` - Upload image
- `DELETE /api/uploads/{path}` - Supprimer image

## Changer un utilisateur en admin

Les utilisateurs créés via `/inscription` ont le role `user` par défaut.

Pour passer admin, modifier directement dans MongoDB :

```javascript
// MongoDB Atlas Console
db.users.updateOne(
  { email: "admin@tdbr.fr" },
  { $set: { role: "admin" } }
)
```

Ou via le shell mongo :
```bash
mongosh "mongodb+srv://tdbr_db_user:tTQw17RApRlMPjkf@tdbr.x5g60ng.mongodb.net/tdbr"

db.users.updateOne(
  { email: "admin@tdbr.fr" },
  { $set: { role: "admin" } }
)
```

## Problèmes courants

### "Class 'MongoDB\Client' not found"

→ Extension MongoDB non installée. Voir étape 1.

### "curl error 60: SSL certificate"

→ Désactiver SSL Composer :
```bash
composer config secure-http false
composer config disable-tls true
```

### MongoDB connection failed

1. Vérifier que l'URI est correcte dans `.env.local`
2. Tester : `curl https://tdbr.x5g60ng.mongodb.net/` (doit répondre)
3. Vérifier l'IP whitelistée dans MongoDB Atlas

### Token JWT invalide

Le token expire après 24h (`JWT_EXPIRATION=86400`).

Se reconnecter pour obtenir un nouveau token.

## Prochaines étapes

1. **Frontend Vue.js** : Configurer `VITE_API_URL=http://localhost:8000`
2. **Créer des données** : Utiliser Postman/Insomnia ou l'interface admin Vue
3. **Déployer** : Voir `DEPLOY-HOSTINGER.md`

## Documentation complète

- **README.md** : Installation détaillée
- **DEPLOY-HOSTINGER.md** : Déploiement production
- **Code** : Tous les contrôleurs dans `src/Controller/Api/`

## Support

Les données MongoDB sont partagées avec l'ancienne API Express.

Aucune migration nécessaire - les collections existent déjà :
- `users`
- `categories`
- `articles`
- `collections`
- `commandes`
- etc.

✅ **L'API est prête à l'emploi !**
