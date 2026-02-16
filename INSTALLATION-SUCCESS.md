# ✅ Installation Réussie - API Symfony TDBR

**Date** : 16 février 2024
**Status** : 🎉 **OPÉRATIONNEL**

## Ce qui a été installé

### 1. Extension PHP MongoDB
- **Version** : 2.2.1 (8.2-ts-vs16-x86_64)
- **Source** : GitHub mongodb/mongo-php-driver
- **Emplacement** : `C:\wamp64\bin\php\php8.2.0\ext\php_mongodb.dll`
- **Activation** : `extension=mongodb` dans php.ini (ligne 952)
- **Vérification** : `php -m | grep mongodb` ✅

### 2. Bibliothèque mongodb/mongodb
- **Version** : 2.2.0
- **Source** : GitHub mongodb/mongo-php-library
- **Installation** : Manuelle (Composer bloqué par SSL)
- **Emplacement** : `vendor/mongodb/mongodb/`
- **Autoload** : PSR-4 configuré

### 3. Bibliothèque firebase/php-jwt
- **Version** : 6.10.1
- **Source** : GitHub firebase/php-jwt
- **Installation** : Manuelle
- **Emplacement** : `vendor/firebase/php-jwt/`
- **Autoload** : PSR-4 configuré

### 4. Certificats SSL
- **Fichier** : `C:\Users\Manu\cacert.pem` (220KB)
- **Configuration PHP** :
  - `curl.cainfo = "C:\Users\Manu\cacert.pem"` (ligne 1944)
  - `openssl.cafile="C:\Users\Manu\cacert.pem"` (ligne 1953)
- **Configuration Composer** :
  - `secure-http: true`
  - `cafile: "C:\\Users\\Manu\\cacert.pem"`

## Test de Connexion

### Commande
```bash
php -r "
require 'vendor/autoload.php';
\$client = new MongoDB\Client('mongodb+srv://...');
var_dump(\$client->listDatabases());
"
```

### API Health Check
```bash
curl http://localhost:8000/api/health
```

**Résultat** :
```json
{
  "status":"OK",
  "message":"API TDBR Symfony fonctionne correctement",
  "timestamp":"2026-02-16T18:49:06+0000",
  "database":{
    "mongodb":"connected"
  }
}
```

✅ **SUCCÈS COMPLET**

## Problèmes Résolus

### 1. Extension MongoDB manquante
- ❌ `Class 'MongoDB\Client' not found`
- ✅ Téléchargé depuis GitHub releases
- ✅ Copié dans ext/ et activé dans php.ini

### 2. Composer SSL bloqué
- ❌ `curl error 60: SSL certificate problem`
- ✅ Téléchargé cacert.pem depuis curl.se
- ✅ Configuré dans php.ini et composer.json
- ⚠️ Composer toujours bloqué → Installation manuelle

### 3. Bibliothèques manquantes
- ❌ Impossible d'installer via `composer install`
- ✅ Téléchargé manuellement depuis GitHub
- ✅ Copié dans vendor/ et configuré autoloader

### 4. Incompatibilité de versions
- ❌ Extension 2.2.1 incompatible avec lib 1.21.3
- ✅ Remplacé par mongodb/mongodb 2.2.0

## Structure Finale

```
site_v3/
├── src/
│   ├── Controller/Api/
│   │   ├── ArticleController.php      ✅
│   │   ├── AuthController.php         ✅
│   │   ├── CategoryController.php     ✅
│   │   ├── CollectionController.php   ✅
│   │   ├── HealthController.php       ✅
│   │   └── UploadController.php       ✅
│   ├── Service/
│   │   ├── MongoDBService.php         ✅
│   │   └── JWTService.php             ✅
│   ├── EventSubscriber/
│   │   └── JWTAuthenticationSubscriber.php  ✅
│   └── Command/
│       └── CreateAdminCommand.php     ✅
├── vendor/
│   ├── mongodb/mongodb/               ✅ v2.2.0
│   ├── firebase/php-jwt/              ✅ v6.10.1
│   └── ...
├── config/
│   └── services.yaml                  ✅
├── public/
│   ├── index.php                      ✅
│   └── uploads/                       ✅
├── .env                                ✅
├── .env.local                          ✅
└── composer.json                       ✅
```

## Démarrage

### En développement
```bash
cd C:\Users\Manu\Documents\TDBR\site_v3
php -S localhost:8000 -t public
```

### Créer un admin
```bash
php bin/console app:create-admin
```

### Tester l'API
```bash
# Health check
curl http://localhost:8000/api/health

# Inscription
curl -X POST http://localhost:8000/api/auth/inscription \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tdbr.fr","password":"admin123","prenom":"Admin","nom":"TDBR"}'

# Connexion (récupère le token)
curl -X POST http://localhost:8000/api/auth/connexion \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tdbr.fr","password":"admin123"}'
```

## Documentation

- **README.md** - Documentation complète
- **QUICKSTART.md** - Démarrage rapide
- **DEPLOY-HOSTINGER.md** - Déploiement production
- **MIGRATION-COMPLETE.md** - Récapitulatif migration
- **COMMANDES-UTILES.md** - Liste commandes
- **FIX-COMPOSER-SSL.md** - Résolution problème SSL
- **STATUS.md** - Status du projet
- **CHANGELOG.md** - Historique versions

## Prochaines Étapes

1. ✅ **Tester toutes les routes API**
   ```bash
   bash test-api.sh
   ```

2. ✅ **Configurer le frontend Vue.js**
   ```env
   VITE_API_URL=http://localhost:8000
   ```

3. ✅ **Déployer sur Hostinger**
   - Suivre DEPLOY-HOSTINGER.md
   - Upload via FTP
   - Configurer .htaccess

## Notes Importantes

### Mise à jour Composer

Si vous avez besoin d'installer de nouveaux packages et que Composer est toujours bloqué par SSL :

**Option 1** : Résoudre SSL (voir FIX-COMPOSER-SSL.md)
**Option 2** : Installation manuelle comme fait ici
**Option 3** : Utiliser un autre PC/environnement

### Autoloader

Les packages installés manuellement sont dans `vendor/composer/installed.json`.

Si vous devez ajouter d'autres packages manuellement :
1. Télécharger depuis GitHub
2. Copier dans `vendor/namespace/package/`
3. Modifier `vendor/composer/installed.json`
4. Lancer `composer dump-autoload`

## Support

En cas de problème :
1. Vérifier les logs : `var/log/dev.log` ou `var/log/prod.log`
2. Tester MongoDB : `php -r "new MongoDB\Client('...');"`
3. Vérifier extension : `php -m | grep mongodb`
4. Health check : `curl http://localhost:8000/api/health`

---

🎉 **L'API Symfony TDBR est pleinement opérationnelle !**

Installation réalisée le 16 février 2024 avec succès.
