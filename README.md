# TDBR - Goodies Personnalisés

Site e-commerce pour la vente de goodies personnalisés créés avec IA générative.

**TDBR** est la marque de **TedYBear** (Emmanuel/Manu), créant des designs originaux inspirés du street art pour des goodies sur les thématiques **Jeux de Société** et **Fromage**.

## 🎨 Identité de la Marque

- **Créations :** Mugs, t-shirts, tote bags, accessoires personnalisés
- **Design :** IA générative avec direction artistique humaine
- **Inspiration :** Street art (Banksy), minimaliste, percutant, irrévérent
- **Thématiques :**
  - 🎲 **Jeux de Société** - Meeples, pions, références cultes pour gamers
  - 🧀 **Fromage** - Humour et gastronomie se rencontrent
- **Valeurs :** Transparence, honnêteté, créativité assumée

> "Parce qu'un goodie, c'est comme un tag : ça doit marquer les esprits."

## 🛠️ Stack Technique

### Backend
- **Framework :** Symfony 6.4 LTS
- **PHP :** 8.2
- **Base de données :** MySQL 8 via **Doctrine ORM** + Doctrine Migrations
- **Authentification :** Symfony Security Component (sessions PHP, bcrypt) — pas de JWT
- **Paiement :** Mollie (+ webhook)
- **Print on demand :** intégration Printful
- **Services :** UploadService (GD), MailerService (Symfony Mailer), CartService (panier en session),
  AccountProvisioningService (création de compte après paiement), Analytics (PageView)

### Frontend
- **Templates :** Twig (rendu côté serveur)
- **CSS :** Tailwind CSS 3.4 (thème custom TDBR)
- **JavaScript :** Alpine.js + SortableJS (drag-and-drop admin)
- **Build :** Webpack Encore 4.x (assets versionnés, lus via `entrypoints.json`)
- **Polices :** **Bricolage Grotesque** (titres) + **Hanken Grotesk** (corps) — woff2 variables **auto-hébergés** (`assets/fonts/`, pas de CDN Google)

### Charte couleurs (Tailwind — « Émeraude »)
```js
colors: {
  primary:   '#2F7A5B',  // Vert émeraude
  secondary: '#4FB48A',  // Vert clair
  accent:    '#E7F4EE',  // Vert très clair
  dark:      '#143027'   // Vert foncé
}
```
> Les tokens (couleurs + polices) sont centralisés dans `tailwind.config.js` / `assets/styles/app.css`
> et synchronisés avec le projet **Claude Design** (`tokens.css`).

### Sécurité
- **CSP** avec nonces par requête (`SecurityHeadersSubscriber`), `font-src 'self'`, headers durcis.
- **CSRF** sur les formulaires et les appels AJAX (token `app`).
- **Rate-limiter** sur le login et les demandes sur-mesure ; honeypots anti-spam.

## 📦 Installation

### Prérequis
- PHP 8.2+ avec extensions : `pdo_mysql`, `gd`, `intl`
- Composer 2.x
- Node.js 18+ et npm
- MySQL 8 (serveur local ou distant)

### Installation locale
```bash
git clone https://github.com/TedYBear/tdbr-php.git
cd tdbr-php

composer install
npm install

cp .env .env.local
# Éditer .env.local : DATABASE_URL et MAILER_DSN

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
npm run build

symfony serve   # ou: php -S localhost:8000 -t public
```

### Base de données (`.env.local`)
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/u538940476_tdbr?serverVersion=8&charset=utf8mb4"
```
Le schéma est géré par **Doctrine Migrations** (`migrations/`). Entités principales :
`Article`, `Variante`, `VarianteTemplate`, `Category`, `ProductCollection`, `Caracteristique`,
`Commande`, `User`, `DemandeSurMesure`, `TypePersonnalisation`, `DepotVente`, `PropositionCommerciale`,
`Avis`, `Message`, `CodeReduction`, `GrillePrix`, `Fournisseur`, `BoutiqueRelais`, `PageView`, `SiteConfig`.

## 🚀 Fonctionnalités

### Pages publiques
- **Home / Présentation** (workflow, tarifs, partenaires, réseaux, livraison)
- **Catalogue** → **Catégorie** → **Collection** → **Article** (galerie, variantes, panier)
- **Demande de personnalisation** depuis la fiche produit (modale, rattachée au compte)
- **Sur-mesure / Devis** générique
- **Panier / Checkout** (paiement Mollie), **Avis** clients, **Contact**
- **Profil** : infos, commandes, codes réduction, propositions, « Mes demandes sur-mesure »
- **Dépôt-vente** (`/mon-depot`) pour les partenaires (rôle dédié)

### Tri & ordre
Catégories, collections et articles s'affichent selon un champ `ordre`, géré graphiquement
en admin (voir « Organisation »).

### Interface admin (`ROLE_ADMIN`)
- **Dashboard** & **Analytiques** de trafic
- **Articles**, **Catégories**, **Collections**, **Templates**, **Caractéristiques**, **Personnalisations**
- **Organisation du catalogue** : réordonnancement **drag-and-drop** (catégories → collections → articles)
- **Commandes**, **Sur-mesure/Devis**, **Messages**, **Avis**
- **Fournisseurs**, **Codes réduction**, **Grilles de prix**, **Boutiques relais**, **Dépôt-vente**
- **Propositions commerciales**, **Marketing**, **Printful**, **Utilisateurs**, **Configuration du site**

### Notifications email
Inscription, confirmation/statut de commande, contact, sur-mesure, propositions, virement, facture acquittée, code cadeau, invitation. Templates responsive aux couleurs de la marque.

### Filtres / fonctions Twig custom
`price`, `date_french`, `truncate`, `to_string`, `csp_nonce`, `encore_entry` (résolution des assets buildés).

## 🚢 Déploiement (Hostinger)

Le déploiement se fait par **`git pull`** ; `public/build/` (assets, polices, JS) est **versionné**
→ **pas de Node sur le serveur**.

```bash
git checkout main && git pull
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

**Checklist :**
- [ ] `composer install --no-dev --optimize-autoloader` (au besoin)
- [ ] `.env.prod.local` : `DATABASE_URL`, `MAILER_DSN`, clés Mollie/Printful
- [ ] Migrations appliquées
- [ ] `cache:clear --env=prod`
- [ ] Permissions `var/` (775) et `public/uploads/` (755)
- [ ] Test routes publiques + admin

> Détail : [DEPLOY-HOSTINGER.md](DEPLOY-HOSTINGER.md).

## 🧑‍💻 Développement
```bash
npm run watch        # build assets en watch
npm run build        # build production
symfony serve        # serveur dev
php bin/console lint:twig templates
php bin/console lint:container
```

## 📚 Documentation
- **[CHANGELOG.md](CHANGELOG.md)** — journal des versions (+ changelogs datés `CHANGELOG-YYYY-MM-DD.md`)
- **[DEPLOY-HOSTINGER.md](DEPLOY-HOSTINGER.md)** — guide de déploiement
- **[SECURITY-AUDIT-2026-06-10.md](SECURITY-AUDIT-2026-06-10.md)** — audit de sécurité
- **[PROFIL_TDBR.md](PROFIL_TDBR.md)** — identité de marque et contexte
- **[COMMANDES-UTILES.md](COMMANDES-UTILES.md)** — commandes fréquentes

## 📄 Licence
Propriétaire - TDBR © 2026 — Créé par TedYBear (Emmanuel)
