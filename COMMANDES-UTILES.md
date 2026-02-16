# Commandes Utiles - TDBR Symfony

Liste de toutes les commandes utiles pour développer, tester et déployer l'API TDBR.

## 🚀 Installation & Démarrage

### Installation initiale

```bash
# Installer les dépendances
composer install

# Si problème SSL
composer config secure-http false
composer config disable-tls true
composer install

# Installer mongodb et jwt manuellement si nécessaire
composer require mongodb/mongodb firebase/php-jwt --no-scripts
```

### Démarrer le serveur

```bash
# Option 1 : Serveur PHP intégré (recommandé pour dev)
php -S localhost:8000 -t public

# Option 2 : Symfony CLI (si installé)
symfony server:start

# Option 3 : Symfony CLI avec port personnalisé
symfony server:start --port=8080

# Option 4 : Avec rechargement automatique
symfony server:start --watch
```

## 👤 Gestion des utilisateurs

### Créer un administrateur

```bash
# Interactif
php bin/console app:create-admin

# Avec options
php bin/console app:create-admin \
  --email=admin@tdbr.fr \
  --password=admin123 \
  --prenom=Admin \
  --nom=TDBR
```

### Promouvoir un utilisateur existant en admin

Via MongoDB :
```javascript
db.users.updateOne(
  { email: "user@example.com" },
  { $set: { role: "admin" } }
)
```

## 🧹 Cache

### Nettoyer le cache

```bash
# Environnement dev
php bin/console cache:clear

# Environnement prod
php bin/console cache:clear --env=prod

# Préchauffer le cache (prod)
php bin/console cache:warmup --env=prod

# Forcer le nettoyage (si problèmes de permissions)
rm -rf var/cache/*
php bin/console cache:clear
```

## 🧪 Tests

### Tester l'API manuellement

```bash
# Windows
test-api.bat

# Linux/Mac
bash test-api.sh

# Ou avec curl manuellement
curl http://localhost:8000/api/health
```

### Test de connexion MongoDB

```bash
php -r "
try {
  \$client = new MongoDB\Client('mongodb+srv://...');
  var_dump(\$client->listDatabases());
  echo 'MongoDB OK' . PHP_EOL;
} catch(\Exception \$e) {
  echo 'Erreur: ' . \$e->getMessage() . PHP_EOL;
}
"
```

### Test JWT

```bash
php -r "
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

\$secret = 'super_secret_key_to_change_in_production';
\$payload = ['userId' => '123', 'email' => 'test@test.com', 'role' => 'admin'];
\$token = JWT::encode(\$payload, \$secret, 'HS256');
echo 'Token: ' . \$token . PHP_EOL;

\$decoded = JWT::decode(\$token, new Key(\$secret, 'HS256'));
echo 'Decoded: ' . json_encode(\$decoded) . PHP_EOL;
"
```

## 📋 Composer

### Installer un nouveau package

```bash
# Package normal
composer require vendor/package

# Package dev uniquement
composer require --dev vendor/package

# Optimiser l'autoloader pour production
composer dump-autoload --optimize --no-dev --classmap-authoritative
```

### Mettre à jour les dépendances

```bash
# Mettre à jour tout
composer update

# Mettre à jour un package spécifique
composer update vendor/package

# Voir les packages obsolètes
composer outdated
```

## 🔍 Debugging

### Voir les logs

```bash
# Dev
tail -f var/log/dev.log

# Prod
tail -f var/log/prod.log

# Suivre en temps réel (Linux/Mac)
tail -f var/log/*.log

# Dernières 50 lignes
tail -50 var/log/prod.log
```

### Lister les routes

```bash
# Toutes les routes
php bin/console debug:router

# Routes API seulement
php bin/console debug:router | grep api

# Détails d'une route
php bin/console debug:router api_category_list
```

### Lister les services

```bash
# Tous les services
php bin/console debug:container

# Services MongoDB
php bin/console debug:container | grep -i mongo

# Détails d'un service
php bin/console debug:container App\\Service\\MongoDBService
```

### Vérifier la configuration

```bash
# Configuration complète
php bin/console debug:config

# Configuration d'un bundle
php bin/console debug:config framework
```

## 🗄️ MongoDB

### Connexion MongoDB Shell

```bash
# Avec mongosh (recommandé)
mongosh "mongodb+srv://tdbr_db_user:***REMOVED-CREDENTIAL***@tdbr.x5g60ng.mongodb.net/tdbr"

# Avec mongo (legacy)
mongo "mongodb+srv://tdbr_db_user:***REMOVED-CREDENTIAL***@tdbr.x5g60ng.mongodb.net/tdbr"
```

### Requêtes MongoDB utiles

```javascript
// Lister les collections
show collections

// Compter les documents
db.users.countDocuments()
db.articles.countDocuments()
db.categories.countDocuments()

// Trouver tous les admins
db.users.find({ role: "admin" })

// Trouver les articles actifs et vedettes
db.articles.find({ actif: true, vedette: true })

// Mettre à jour un utilisateur en admin
db.users.updateOne(
  { email: "admin@tdbr.fr" },
  { $set: { role: "admin" } }
)

// Supprimer un article
db.articles.deleteOne({ slug: "article-test" })

// Voir les index
db.categories.getIndexes()
```

## 📦 Build pour production

### Préparer pour déploiement

```bash
# 1. Nettoyer
rm -rf var/cache/* var/log/*

# 2. Installer dépendances prod
composer install --no-dev --optimize-autoloader --no-scripts

# 3. Optimiser autoloader
composer dump-autoload --optimize --no-dev --classmap-authoritative

# 4. Nettoyer cache prod
php bin/console cache:clear --env=prod --no-debug

# 5. Préchauffer cache
php bin/console cache:warmup --env=prod --no-debug

# 6. Définir permissions
chmod -R 755 var/cache var/log public/uploads
```

## 🌐 CORS & Headers

### Tester CORS

```bash
# Préflight OPTIONS request
curl -X OPTIONS http://localhost:8000/api/categories \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: GET" \
  -v

# GET avec Origin
curl http://localhost:8000/api/categories \
  -H "Origin: http://localhost:3000" \
  -v
```

## 🔐 Sécurité

### Générer un secret JWT fort

```bash
# Linux/Mac
openssl rand -base64 32

# PHP
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Windows PowerShell
[Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
```

### Générer APP_SECRET Symfony

```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

### Hasher un mot de passe

```bash
php -r "echo password_hash('mon-mot-de-passe', PASSWORD_BCRYPT) . PHP_EOL;"
```

## 📤 Upload de fichiers

### Test upload image

```bash
# Upload
curl -X POST http://localhost:8000/api/uploads/image \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "image=@/path/to/image.jpg"

# Supprimer
curl -X DELETE http://localhost:8000/api/uploads/image-123.jpg \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

## 🔄 Git

### Initialiser le repo

```bash
git init
git add .
git commit -m "Initial commit - Symfony API TDBR"
```

### Créer .gitignore s'il manque

```bash
echo "/.env.local
/.env.local.php
/.env.*.local
/var/
/vendor/
/public/uploads/*
!/.public/uploads/.gitkeep" > .gitignore
```

## 🐛 Troubleshooting

### Extension MongoDB manquante

```bash
# Vérifier si installée
php -m | grep mongodb

# Vérifier php.ini
php --ini

# Tester manuellement
php -r "var_dump(extension_loaded('mongodb'));"
```

### Erreur permissions

```bash
# Réparer permissions var/
chmod -R 755 var/

# Réparer propriétaire (Linux)
chown -R www-data:www-data var/

# Réparer propriétaire (si utilisateur spécifique)
chown -R $USER:$USER var/
```

### Composer échoue

```bash
# Supprimer et réinstaller
rm -rf vendor/ composer.lock
composer install

# Forcer la résolution
composer update --with-dependencies

# Ignorer les erreurs de plateforme
composer install --ignore-platform-reqs
```

### MongoDB connection failed

```bash
# Vérifier la connexion
ping tdbr.x5g60ng.mongodb.net

# Tester avec curl
curl https://tdbr.x5g60ng.mongodb.net/

# Vérifier les credentials dans .env.local
cat .env.local | grep MONGO_URI
```

## 📊 Performances

### Analyser les performances

```bash
# Activer le profiler en dev (déjà activé)
# Accéder à /_profiler

# Voir les requêtes lentes MongoDB
# Activer dans MongoDB Atlas: Database → Profiler

# Benchmark simple
ab -n 100 -c 10 http://localhost:8000/api/categories
```

### Optimiser

```bash
# Activer OPcache en production (dans php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000

# Utiliser APCu pour le cache Symfony
composer require symfony/cache

# Configurer dans config/packages/cache.yaml
```

## 🎯 Commandes courantes combinées

### Reset complet

```bash
rm -rf var/cache/* var/log/* vendor/ composer.lock
composer install
php bin/console cache:clear
```

### Déploiement rapide (FTP)

```bash
# En local
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod

# Upload via FTP:
# - /src
# - /config
# - /public
# - /vendor (si pas de composer sur serveur)
# - .env (pas .env.local !)

# Sur serveur (SSH)
php bin/console cache:clear --env=prod
chmod -R 755 var/
```

---

**💡 Astuce** : Ajouter ces alias dans votre `.bashrc` ou `.zshrc` :

```bash
alias sf='php bin/console'
alias sfc='php bin/console cache:clear'
alias sfs='php -S localhost:8000 -t public'
alias sfr='php bin/console debug:router'
```

Utilisation :
```bash
sf cache:clear
sfc
sfs
sfr | grep api
```
