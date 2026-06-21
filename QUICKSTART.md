# 🚀 Démarrage rapide — TDBR (Symfony 6.4 · MySQL · Doctrine)

Stack : Symfony 6.4 LTS, PHP 8.2, MySQL 8 (Doctrine ORM), Twig, Tailwind, Alpine.js, Webpack Encore.

## 1. Prérequis
- PHP 8.2+ avec extensions `pdo_mysql`, `gd`, `intl`
- Composer 2.x, Node.js 18+ / npm
- MySQL 8 (local ou distant)

Vérifier :
```bash
php -v
php -m | grep -iE "pdo_mysql|gd|intl"
```

## 2. Installation
```bash
git clone https://github.com/TedYBear/tdbr-php.git
cd tdbr-php

composer install
npm install
```

## 3. Configuration

Copier l'env et éditer `.env.local` :
```bash
cp .env .env.local
```
```env
# .env.local
DATABASE_URL="mysql://user:password@127.0.0.1:3306/tdbr?serverVersion=8&charset=utf8mb4"
MAILER_DSN="null://null"   # ou gmail://user:pass@default / smtp://...
APP_ENV=dev
```
> Clés Mollie / Printful : à renseigner également si paiement / print on demand utilisés.

## 4. Base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 5. Assets
```bash
npm run build        # ou: npm run watch (dev, hot reload)
```

## 6. Lancer
```bash
symfony serve        # ou: php -S localhost:8000 -t public
```
→ http://localhost:8000 (admin sous `/admin`).

## 7. Créer un admin
```bash
# Donner le rôle admin à un compte existant :
php bin/console dbal:run-sql "UPDATE users SET roles='[\"ROLE_ADMIN\"]' WHERE email='admin@tdbr.fr'"
```
> Le hash du mot de passe doit être généré par Symfony (`UserPasswordHasher`, bcrypt).
> Astuce : `php bin/console security:hash-password`.

## ✅ Vérifications
```bash
php bin/console lint:twig templates
php bin/console lint:container
php bin/console debug:router | grep -i admin
```

## 🩺 Dépannage
- **Assets non chargés / 404 CSS** : `npm run build` puis `php bin/console cache:clear`.
  Les balises sont générées via `entrypoints.json` (fonction Twig `encore_entry`) — pas de hash à coder en dur.
- **Erreur DB** : vérifier `DATABASE_URL` et que MySQL tourne ; `doctrine:migrations:status`.
- **CSP bloque un script** : utiliser `nonce="{{ csp_nonce() }}"` sur les `<script>` inline.

> Guide de déploiement : [DEPLOY-HOSTINGER.md](DEPLOY-HOSTINGER.md) · Commandes : [COMMANDES-UTILES.md](COMMANDES-UTILES.md)
