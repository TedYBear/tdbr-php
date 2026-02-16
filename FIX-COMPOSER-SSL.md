# Résoudre le problème SSL de Composer

## Problème

```
curl error 60: SSL certificate problem: unable to get local issuer certificate
```

## Solutions

### Solution 1 : Télécharger le certificat CA (Recommandé)

1. **Télécharger cacert.pem**
   - Aller sur : https://curl.se/docs/caextract.html
   - Télécharger : `cacert.pem`
   - Sauvegarder dans : `C:\cacert.pem`

2. **Configurer PHP**

   Éditer `C:\wamp64\bin\php\php8.2.0\php.ini` :

   Chercher et modifier ces lignes :
   ```ini
   curl.cainfo = "C:\cacert.pem"
   openssl.cafile = "C:\cacert.pem"
   ```

   Si elles sont commentées (`;` devant), retirer le `;`

3. **Redémarrer WAMP**

4. **Tester**
   ```bash
   cd C:\Users\Manu\Documents\TDBR\site_v3
   composer install
   ```

### Solution 2 : Utiliser une machine virtuelle Linux

Si Solution 1 ne fonctionne pas :

1. Installer WSL2 (Windows Subsystem for Linux)
   ```powershell
   wsl --install
   ```

2. Dans WSL, installer PHP et Composer
   ```bash
   sudo apt update
   sudo apt install php php-cli composer
   ```

3. Copier le projet dans WSL et installer
   ```bash
   cd /mnt/c/Users/Manu/Documents/TDBR/site_v3
   composer install
   ```

### Solution 3 : Téléchargement manuel des packages

1. **mongodb/mongodb v1.19.3**
   - Télécharger : https://github.com/mongodb/mongo-php-library/releases/tag/1.19.3
   - Extraire dans : `vendor/mongodb/mongodb/`

2. **firebase/php-jwt v6.10.1**
   - Télécharger : https://github.com/firebase/php-jwt/releases/tag/v6.10.1
   - Extraire dans : `vendor/firebase/php-jwt/`

3. **Installer autoloader**
   ```bash
   composer dump-autoload
   ```

### Solution 4 : Utiliser un autre ordinateur

Si vous avez accès à un autre PC/Mac sans problème SSL :

1. Copier le projet sur l'autre ordinateur
2. Lancer `composer install`
3. Recopier le dossier `vendor/` généré vers votre PC Windows

## Vérification après fix

```bash
cd C:\Users\Manu\Documents\TDBR\site_v3
composer install
```

Devrait afficher :
```
Installing dependencies from lock file
Package operations: X installs, 0 updates, 0 removals
  - Installing mongodb/mongodb (1.19.3)
  - Installing firebase/php-jwt (v6.10.1)
  ...
```

## Test final

```bash
php -S localhost:8000 -t public
```

Puis :
```bash
curl http://localhost:8000/api/health
```

Devrait retourner :
```json
{
  "status": "OK",
  "database": {
    "mongodb": "connected"
  }
}
```
