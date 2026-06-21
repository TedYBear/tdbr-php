# Commandes Utiles - TDBR

Stack : Symfony 6.4 · PHP 8.2 · MySQL 8 (Doctrine ORM) · Twig · Tailwind · Webpack Encore.

## 🚀 Démarrage

```bash
composer install
npm install
symfony serve            # ou: php -S localhost:8000 -t public
npm run watch            # build assets en continu (dev)
```

## 🗄️ Base de données (Doctrine)

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console make:migration            # générer une migration depuis les entités
php bin/console doctrine:schema:validate  # cohérence entités <-> schéma

# Requête SQL rapide
php bin/console dbal:run-sql "SELECT id,email,roles FROM users LIMIT 10"
```

## 👤 Utilisateurs / admin

```bash
# Hash d'un mot de passe (à coller dans la colonne password)
php bin/console security:hash-password

# Promouvoir un compte en admin
php bin/console dbal:run-sql "UPDATE users SET roles='[\"ROLE_ADMIN\"]' WHERE email='admin@tdbr.fr'"
```

## 🎨 Assets (Webpack Encore)

```bash
npm run build            # build production (public/build/)
npm run watch            # watch dev
```
> Les `<link>`/`<script>` sont générés via `entrypoints.json` (fonction Twig `encore_entry`) :
> aucun hash à coder en dur. Après un build, `public/build/` est versionné (déploiement sans Node).

## 🧹 Cache

```bash
php bin/console cache:clear
php bin/console cache:clear --env=prod
rm -rf var/cache/* && php bin/console cache:clear   # si soucis de permissions
```

## 🔍 Debug / vérifications

```bash
php bin/console lint:twig templates
php bin/console lint:container
php bin/console debug:router | grep -i admin
php bin/console debug:router admin_organisation
php bin/console debug:container | grep -i repository
tail -f var/log/dev.log         # logs dev
tail -50 var/log/prod-$(date +%Y-%m-%d).log   # logs prod (Hostinger)
```

## 📋 Composer

```bash
composer require vendor/package
composer require --dev vendor/package
composer update vendor/package
composer dump-autoload --optimize --no-dev --classmap-authoritative
composer outdated
```

## 🔐 Sécurité

```bash
# APP_SECRET
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
# Hash mot de passe (préférer la commande Symfony ci-dessus)
php bin/console security:hash-password
```
Rappels : CSP avec nonces (`csp_nonce()`), CSRF token `app` (formulaires + AJAX header `X-CSRF-Token`),
rate-limiter (login + sur-mesure).

## 🚢 Déploiement (Hostinger, git pull)

```bash
git checkout main && git pull
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```
> `public/build/` (assets, polices, JS) est versionné → pas de `npm` sur le serveur.
> Détail : [DEPLOY-HOSTINGER.md](DEPLOY-HOSTINGER.md).

## 🐛 Troubleshooting

```bash
# Extensions PHP
php -m | grep -iE "pdo_mysql|gd|intl"

# Connexion DB
php bin/console doctrine:migrations:status

# CSS/JS non chargés
npm run build && php bin/console cache:clear

# Permissions (Linux)
chmod -R 775 var/ && chmod -R 755 public/uploads
```

---

**💡 Alias pratiques** (`.bashrc` / `.zshrc`) :
```bash
alias sf='php bin/console'
alias sfc='php bin/console cache:clear'
alias sfm='php bin/console doctrine:migrations:migrate --no-interaction'
alias sfr='php bin/console debug:router'
```
