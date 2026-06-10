# Déploiement Symfony sur Hostinger (via Git)

Guide de déploiement de l'application TDBR sur Hostinger.

> **Architecture actuelle** : application **monolithe Symfony + Twig** (rendu côté serveur),
> base de données **MySQL** via Doctrine ORM. Il n'y a plus de frontend Vue séparé ni de MongoDB.

## Architecture

- **Application** : Symfony 6.3 (PHP ≥ 8.1), templates Twig
- **Base de données** : MySQL 8.0 (Doctrine ORM + migrations)
- **Racine web** : le `.htaccess` à la racine du dépôt route toutes les requêtes vers `public/index.php`
- **Uploads** : stockés dans `public/uploads/`
- **Paiement** : Mollie ; impression à la demande : Printful

## Prérequis

1. Hébergement Hostinger avec :
   - PHP **8.1+**
   - Base MySQL créée (hPanel → Bases de données MySQL)
   - Accès **SSH** activé
   - Composer (installé globalement, ou via `composer.phar` local)
2. Domaine `tedybear.fr` pointant vers le dossier contenant le dépôt (racine routée vers `public/`)
3. Le dépôt cloné sur le serveur depuis `https://github.com/TedYBear/tdbr-php.git`

## Configuration prod : `.env.local`

À créer **directement sur le serveur** (jamais committé). Contient les vrais secrets :

```env
APP_ENV=prod
APP_DEBUG=0

# Générer : php -r "echo bin2hex(random_bytes(16));"
APP_SECRET=<valeur_aleatoire>

###> Base de données MySQL ###
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=<nom_base_hostinger>
DB_USER=<user_mysql_hostinger>
DB_PASSWORD=<mot_de_passe_mysql>
DB_VERSION=8.0
###< Base de données ###

###> Mailer ###
MAILER_DSN=smtp://<user>:<app_password>@smtp.gmail.com:587?encryption=tls
###< Mailer ###

###> JWT ###
# Générer : php -r "echo bin2hex(random_bytes(32));"
JWT_SECRET=<valeur_aleatoire_64_hex>
JWT_EXPIRATION=86400
###< JWT ###

###> Mollie ###
MOLLIE_API_KEY=live_<cle_mollie_prod>
###< Mollie ###

###> Printful ###
PRINTFUL_API_KEY=<cle_printful>
PRINTFUL_STORE_ID=<store_id>
###< Printful ###
```

> **Important** : `APP_SECRET` et `JWT_SECRET` doivent être de vraies valeurs aléatoires,
> jamais les placeholders du `.env` versionné.

## Premier déploiement (clone initial)

```bash
ssh uXXXXXXXX@tedybear.fr
cd ~/public_html        # ou le dossier servi par le domaine

git clone https://github.com/TedYBear/tdbr-php.git .

# Créer le .env.local (voir section ci-dessus)
nano .env.local

composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

chmod -R 755 var
```

Vérifier que la racine web pointe bien vers le dossier du dépôt (le `.htaccess` racine
redirige vers `public/index.php`), ou faire pointer le domaine directement sur `public/`.

## Mise à jour (déploiement courant)

```bash
ssh uXXXXXXXX@tedybear.fr
cd ~/public_html

git pull origin main

composer install --no-dev --optimize-autoloader   # si composer.lock a changé
php bin/console doctrine:migrations:migrate --no-interaction --env=prod   # si nouvelles migrations
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### ⚠️ Cas particulier — après réécriture d'historique (force-push)

L'historique git a été réécrit le 2026-06-10 (purge de secrets exposés). Le **premier** `git pull`
suivant échouera (historiques divergents). Resynchroniser **une seule fois** ainsi :

```bash
cd ~/public_html
git fetch origin
git reset --hard origin/main
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Les déploiements suivants reprennent le `git pull` normal.

> Cette mise à jour ajoute la dépendance `symfony/rate-limiter` (throttling du login) :
> le `composer install` est donc **obligatoire**, sinon le login plantera.

## Troubleshooting

### `ClassNotFoundError: DebugBundle` (ou WebProfilerBundle / MakerBundle)
```
Attempted to load class "DebugBundle" from namespace "Symfony\Bundle\DebugBundle".
```
**Cause** : le serveur tourne en environnement **dev** alors que `composer install --no-dev`
a retiré les bundles de dev. Symfony cherche `DebugBundle` (déclaré `['dev' => true]` dans
`config/bundles.php`) qui n'est pas installé.

**Correctif** : forcer l'environnement prod dans `.env.local` :
```env
APP_ENV=prod
APP_DEBUG=0
```
puis :
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```
Alternative propre : `composer dump-env prod` (compile `.env.local.php` figé en prod),
à relancer après chaque déploiement qui modifie les fichiers `.env`.

### Erreur 500
```bash
tail -n 50 ~/public_html/var/log/prod.log
```
Ou hPanel → Fichiers → Journaux d'erreurs.

### Connexion MySQL refusée
- Vérifier `DB_*` dans `.env.local` (nom de base, user, mot de passe Hostinger)
- Vérifier que la base existe dans hPanel

### Permissions sur var/
```bash
chmod -R 755 var
```

### Cache obsolète après déploiement
```bash
rm -rf var/cache/prod
php bin/console cache:warmup --env=prod
```

## Sauvegarde

```bash
# Uploads + config locale
cd ~/public_html
tar -czf ~/backup-$(date +%Y%m%d).tar.gz public/uploads .env.local

# Base de données
mysqldump -u <user> -p <nom_base> > ~/db-backup-$(date +%Y%m%d).sql
```

## CRON (optionnel)

hPanel → Avancé → Tâches Cron :
```bash
# Nettoyage cache hebdomadaire
0 3 * * 0 cd ~/public_html && php bin/console cache:clear --env=prod
```
