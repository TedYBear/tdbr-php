# Déploiement Symfony sur Hostinger Business

Guide complet pour déployer l'application Symfony TDBR sur un hébergement Hostinger Business.

## Architecture cible

- **Frontend** : Vue.js statique sur Hostinger (public_html/)
- **Backend** : Symfony PHP API sur Hostinger (dans un sous-dossier)
- **Database** : MongoDB Atlas (cloud)
- **Images** : Uploads stockés sur Hostinger

## Prérequis

1. Hébergement Hostinger Business avec :
   - PHP 8.1+ activé
   - Accès FTP/SFTP
   - Accès SSH (optionnel mais recommandé)
   - Extension PHP MongoDB activée dans hPanel

2. Domaine configuré (ex: `tedybear.fr`)

3. MongoDB Atlas accessible depuis Hostinger (whitelist IP)

## Étape 1 : Activer l'extension MongoDB

1. Se connecter à hPanel Hostinger
2. Aller dans **Advanced → PHP Configuration**
3. Sélectionner la version PHP 8.1+
4. Dans **Extensions**, activer `mongodb`
5. Sauvegarder et redémarrer

Vérifier via SSH :
```bash
php -m | grep mongodb
```

## Étape 2 : Préparer l'application en local

### Build de production

```bash
cd C:\Users\Manu\Documents\TDBR\site_v3

# Nettoyer le cache
php bin/console cache:clear

# Installer les dépendances pour production
composer install --no-dev --optimize-autoloader --no-scripts

# Optimiser l'autoloader
composer dump-autoload --optimize --no-dev --classmap-authoritative
```

## Étape 3 : Configuration des variables d'environnement

Sur le serveur Hostinger, créer `.env.local` :

```bash
# Via SSH
ssh u123456789@tedybear.fr
cd ~/public_html/api

# Créer .env.local
nano .env.local
```

Contenu de `.env.local` :

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=GENERATE-A-NEW-SECRET-HERE

###> MongoDB Configuration ###
MONGO_URI=mongodb+srv://tdbr_db_user:tTQw17RApRlMPjkf@tdbr.x5g60ng.mongodb.net/?appName=TDBR
MONGO_DB_NAME=tdbr

ALLOWED_ORIGINS=https://tedybear.fr,https://www.tedybear.fr
###< MongoDB Configuration ###

###> JWT Configuration ###
JWT_SECRET=production-jwt-secret-super-securise-a-changer
JWT_EXPIRATION=86400
###< JWT Configuration ###

###> Upload Configuration ###
UPLOAD_DIR=/home/u123456789/public_html/uploads
###< Upload Configuration ###
```

**IMPORTANT** : Remplacer `u123456789` par votre vraie ID utilisateur Hostinger.

## Étape 4 : Structure de déploiement

### Option A : API dans un sous-dossier `/api`

```
/home/u123456789/public_html/
├── index.html              # Frontend Vue (root)
├── assets/                 # Assets frontend
├── uploads/                # Images uploadées (partagé)
└── api/                    # Backend Symfony
    ├── bin/
    ├── config/
    ├── public/
    │   └── index.php       # Point d'entrée API
    ├── src/
    ├── var/
    ├── vendor/
    ├── .env
    └── .env.local
```

URLs :
- Frontend : `https://tedybear.fr/`
- API : `https://tedybear.fr/api/`
- Images : `https://tedybear.fr/uploads/`

### Option B : API dans un sous-domaine `api.tedybear.fr`

Créer un sous-domaine dans hPanel pointant vers `/public_html/api/public`

```
/home/u123456789/
├── public_html/            # Frontend tedybear.fr
│   ├── index.html
│   ├── assets/
│   └── uploads/
└── api.tedybear.fr/        # Backend API
    ├── bin/
    ├── config/
    ├── public/
    │   └── index.php
    ├── src/
    └── ...
```

URLs :
- Frontend : `https://tedybear.fr/`
- API : `https://api.tedybear.fr/`
- Images : `https://tedybear.fr/uploads/` (CORS requis)

## Étape 5 : Upload via FTP

### Connexion FTP

- **Hôte** : `ftp.tedybear.fr`
- **Utilisateur** : Votre username Hostinger
- **Mot de passe** : Votre password
- **Port** : 21 (FTP) ou 22 (SFTP)

### Fichiers à uploader (API)

Uploader dans `/public_html/api/` :

```
✅ /bin
✅ /config
✅ /public
✅ /src
✅ /vendor (si pas d'accès SSH pour composer install)
✅ .env
✅ composer.json
✅ composer.lock
✅ symfony.lock

❌ /var/cache (sera régénéré)
❌ /var/log (sera régénéré)
❌ .env.local (créer directement sur le serveur)
❌ .git
```

### Créer les dossiers nécessaires

Via SSH :

```bash
cd ~/public_html
mkdir -p api/var/cache api/var/log uploads
chmod -R 755 api/var uploads
```

## Étape 6 : Configuration Apache

### Pour Option A (API dans /api)

Créer `public_html/api/.htaccess` :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Forcer HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Rediriger vers public/index.php
    RewriteCond %{REQUEST_URI} !^/api/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Créer `public_html/api/public/.htaccess` :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Rediriger toutes les requêtes vers index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Headers CORS
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

### Pour le frontend (Vue.js)

Créer `public_html/.htaccess` :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Forcer HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # NE PAS rediriger /api
    RewriteCond %{REQUEST_URI} ^/api
    RewriteRule ^ - [L]

    # NE PAS rediriger /uploads
    RewriteCond %{REQUEST_URI} ^/uploads
    RewriteRule ^ - [L]

    # SPA fallback pour Vue Router (seulement pour les autres routes)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>

# Cache pour les assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>
```

## Étape 7 : Installer les dépendances via SSH (recommandé)

Si vous avez accès SSH :

```bash
ssh u123456789@tedybear.fr

cd ~/public_html/api

# Installer Composer localement si absent
curl -sS https://getcomposer.org/installer | php
mv composer.phar composer

# Installer les dépendances
php composer install --no-dev --optimize-autoloader

# Nettoyer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Permissions
chmod -R 755 var/cache var/log
```

## Étape 8 : Configurer MongoDB Atlas

1. Se connecter à MongoDB Atlas : https://cloud.mongodb.com
2. Aller dans **Network Access**
3. Cliquer sur **Add IP Address**
4. Récupérer l'IP de votre serveur Hostinger :

```bash
# Via SSH
curl ifconfig.me
```

5. Ajouter cette IP à la whitelist MongoDB Atlas

## Étape 9 : Tester l'API

### Health check

```bash
curl https://tedybear.fr/api/health
```

Devrait retourner :
```json
{
  "status": "OK",
  "message": "API TDBR Symfony fonctionne correctement",
  "database": {
    "mongodb": "connected"
  }
}
```

### Tester depuis le frontend

Modifier `apps/web/.env.production` :

```env
VITE_API_URL=https://tedybear.fr/api
```

Rebuild le frontend :

```bash
cd C:\Users\Manu\Documents\TDBR\site_V2\apps\web
npm run build
```

Upload `dist/*` vers `public_html/`

## Étape 10 : Configurer les CRON jobs (optionnel)

Dans hPanel → Advanced → Cron Jobs :

```bash
# Nettoyer le cache quotidiennement
0 3 * * * cd ~/public_html/api && php bin/console cache:clear --env=prod
```

## Troubleshooting

### Erreur 500 : Internal Server Error

Vérifier les logs :

```bash
# Via SSH
tail -f ~/public_html/api/var/log/prod.log
```

Ou dans hPanel → Files → Error Logs

### MongoDB connection refused

1. Vérifier que l'IP du serveur est whitelistée dans MongoDB Atlas
2. Vérifier `MONGO_URI` dans `.env.local`
3. Tester la connexion :

```bash
php -r "try { \$client = new MongoDB\Client('mongodb+srv://...'); \$client->listDatabases(); echo 'OK'; } catch(\Exception \$e) { echo \$e->getMessage(); }"
```

### Extension MongoDB non trouvée

Activer dans hPanel → PHP Configuration → Extensions → `mongodb`

### Permissions denied sur var/cache

```bash
chmod -R 755 var/cache var/log
chown -R u123456789:u123456789 var
```

### CORS errors depuis le frontend

Vérifier :
1. `ALLOWED_ORIGINS` dans `.env.local` contient le domaine du frontend
2. Headers CORS dans `.htaccess`

### Images ne se chargent pas

1. Vérifier que `UPLOAD_DIR` pointe vers le bon chemin
2. Vérifier les permissions sur `/uploads` :

```bash
chmod -R 755 ~/public_html/uploads
```

## Mise à jour de l'application

```bash
# 1. En local, rebuild
composer install --no-dev --optimize-autoloader
php bin/console cache:clear

# 2. Upload les fichiers modifiés via FTP
# (src/, config/, templates/, etc.)

# 3. Via SSH sur le serveur
ssh u123456789@tedybear.fr
cd ~/public_html/api

# 4. Nettoyer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## Backup

### Base de données (MongoDB Atlas)

MongoDB Atlas fait des backups automatiques quotidiens.

### Fichiers

Sauvegarder régulièrement :
- `/uploads` (images)
- `.env.local` (configuration)

```bash
# Via SSH
cd ~/public_html
tar -czf backup-$(date +%Y%m%d).tar.gz uploads api/.env.local
```

## Support

En cas de problème :
- Logs Symfony : `var/log/prod.log`
- Logs Apache : hPanel → Files → Error Logs
- Logs PHP : hPanel → Advanced → PHP Configuration → Display Errors (temporairement)
