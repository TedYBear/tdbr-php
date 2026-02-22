# Analyse Technique et Fonctionnelle — TDBR site_v3

> Rédigé le 2026-02-22 pour reprise rapide des travaux.

---

## 1. Vue d'ensemble

**Projet** : E-commerce TDBR — goodies personnalisés avec designs IA
**Repo Git** : `https://github.com/TedYBear/tdbr-php.git`
**Stack** : Symfony 6.3 · PHP 8.1+ · MySQL 8.0 · Twig · Tailwind CSS 3.4 · Alpine.js 3.13
**Démarrage local** : `php -S localhost:8000 -t public`
**Domaine prod** : `tedybear.fr` · Email : `boutique.tdbr@gmail.com`

---

## 2. Structure des dossiers

```
site_v3/
├── assets/                  # Sources JS/CSS (Webpack Encore)
│   ├── app.js
│   └── styles/app.css       # Tailwind + custom
├── bin/console              # CLI Symfony
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml    # MySQL, ORM
│   │   ├── mailer.yaml      # Mailer (null en dev, smtp en prod)
│   │   ├── security.yaml    # Auth, rôles, access control
│   │   └── twig.yaml
│   ├── routes.yaml          # Auto-discovery via attributs #[Route]
│   └── services.yaml        # Auto-wiring, injection dépendances
├── migrations/              # Migrations Doctrine versionnées
├── public/
│   ├── index.php            # Point d'entrée
│   ├── build/               # Assets compilés (CSS/JS)
│   └── uploads/             # Images uploadées (gitignored sauf exceptions)
│       ├── articles/
│       ├── categories/
│       ├── collections/
│       ├── fournisseurs/    # logo-printful.svg, vista.png (forcés dans git)
│       ├── general/         # baptiste.jpg, fromage_dessert.jpg (forcés)
│       └── temp/
├── src/
│   ├── Controller/
│   │   ├── Admin/           # 11 controllers admin (ROLE_ADMIN)
│   │   ├── Api/             # UploadController (API REST partielle)
│   │   ├── AvisController.php
│   │   ├── DevisController.php
│   │   └── PublicController.php
│   ├── Entity/              # 14 entités Doctrine
│   ├── EventSubscriber/     # NavbarSubscriber, JWTAuthenticationSubscriber
│   ├── Form/                # Types de formulaires Symfony
│   ├── Repository/          # 14 repositories
│   ├── Security/            # Gestion sécurité
│   ├── Service/             # CartService, UploadService, MailerService, JWTService
│   └── Twig/                # AppExtension (filtres Twig)
├── templates/
│   ├── admin/               # Templates back-office
│   ├── auth/                # Connexion, inscription, profil
│   ├── components/          # Composants réutilisables
│   ├── emails/              # Templates emails
│   ├── layout/              # navbar.html.twig, footer.html.twig, flash_messages
│   └── public/              # Pages frontend
├── tdbr_schema.sql          # Schéma MySQL complet (référence)
├── tdbr_data.sql            # Données initiales
├── .env                     # Config environnement (non committé en prod)
├── webpack.config.js
├── composer.json
└── package.json
```

---

## 3. Entités Doctrine

### User
| Champ | Type | Notes |
|-------|------|-------|
| id | INT PK | |
| email | VARCHAR 180 | UNIQUE |
| roles | JSON | ROLE_USER, ROLE_ADMIN |
| password | VARCHAR 255 | bcrypt |
| prenom | VARCHAR 100 | nullable |
| nom | VARCHAR 100 | nullable |
| telephone | VARCHAR 20 | nullable |
| createdAt | DateTimeImmutable | |

### Category
| Champ | Type | Notes |
|-------|------|-------|
| id | INT PK | |
| nom | VARCHAR 200 | |
| slug | VARCHAR 200 | UNIQUE |
| description | TEXT | nullable |
| image | VARCHAR 500 | nullable |
| ordre | INT | default 0 |
| actif | BOOLEAN | default true |
| createdAt | DateTimeImmutable | |

Relations : `OneToMany → ProductCollection`

### ProductCollection
| Champ | Type | Notes |
|-------|------|-------|
| id | INT PK | |
| nom | VARCHAR 200 | |
| slug | VARCHAR 200 | UNIQUE |
| description | TEXT | nullable |
| image | VARCHAR 500 | nullable |
| ordre | INT | default 0 |
| actif | BOOLEAN | default true |
| createdAt | DateTimeImmutable | |

Relations : `ManyToOne → Category` · `OneToMany → Article`

### Article
| Champ | Type | Notes |
|-------|------|-------|
| id | INT PK | |
| nom | VARCHAR 300 | |
| slug | VARCHAR 300 | UNIQUE |
| description | TEXT | nullable |
| prixBase | DECIMAL 10,2 | default 0.0 |
| actif | BOOLEAN | default true |
| enVedette | BOOLEAN | default false |
| personnalisable | BOOLEAN | default false |
| ordre | INT | default 0 |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | nullable |

Relations : `ManyToOne → ProductCollection` · `ManyToOne → Fournisseur` · `OneToMany → ArticleImage` (cascade delete) · `OneToMany → Variante` (cascade delete)

### ArticleImage
`id · article_id (FK) · url · alt · ordre`

### Variante
`id · article_id (FK) · nom · sku · prix (DECIMAL nullable) · stock · actif`

### Commande
Stockage en JSON pour flexibilité maximale.

| Champ | Type | Notes |
|-------|------|-------|
| id | INT PK | |
| numero | VARCHAR 50 | UNIQUE |
| client | JSON | `{ prenom, nom, email, telephone }` |
| adresseLivraison | JSON | `{ adresse, complementAdresse, codePostal, ville, pays }` |
| articles | JSON | `[{ articleId, nom, prix, quantity, variant, image }]` |
| total | DECIMAL 10,2 | |
| modePaiement | VARCHAR 50 | carte / virement / paypal |
| notes | TEXT | nullable |
| statut | VARCHAR 50 | en_attente → validee → en_cours → expediee → livree → annulee |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | nullable |

### Devis
`id · nom · email · telephone · concept · contexte · supports (JSON) · autreSupport · quantite · moyenContact · messageAdditionnel · statut (nouveau/en_cours/repondu/annule) · notesAdmin · createdAt · updatedAt`

### Avis
`id · user_id (FK) · contenu · note (1-5) · photoFilename · visible (default false) · ordre · createdAt`

### Message (contact)
`id · nom · email · sujet · message · lu (default false) · createdAt`

### Fournisseur
`id · nom · url · logoFilename · createdAt`

### Caracteristique + CaracteristiqueValeur
Système de caractéristiques configurables pour les articles.
`Caracteristique : id · nom · type · obligatoire`
`CaracteristiqueValeur : id · caracteristique_id · valeur`

### VarianteTemplate
Templates de variantes réutilisables.
`id · nom · description`
`ManyToMany → Caracteristique`

---

## 4. Routes

### Public (`PublicController`)

| URL | Méthode | Route name | Auth |
|-----|---------|------------|------|
| `/` | GET | `home` | PUBLIC |
| `/presentation` | GET | `presentation` | PUBLIC |
| `/presentation/ma-facon-de-travailler` | GET | `presentation_workflow` | PUBLIC |
| `/presentation/partenaires` | GET | `presentation_partenaires` | PUBLIC |
| `/catalogue` | GET | `catalogue` | PUBLIC |
| `/categorie/{slug}` | GET | `categorie` | PUBLIC |
| `/collection/{slug}` | GET | `collection` | PUBLIC |
| `/article/{slug}` | GET | `article` | PUBLIC |
| `/panier` | GET | `panier` | PUBLIC |
| `/panier/add` | POST | `panier_add` | PUBLIC |
| `/panier/remove/{itemId}` | POST | `panier_remove` | PUBLIC |
| `/panier/update/{itemId}` | POST | `panier_update` | PUBLIC |
| `/panier/clear` | POST | `panier_clear` | PUBLIC |
| `/checkout` | GET/POST | `checkout` | PUBLIC |
| `/confirmation/{id}` | GET | `confirmation` | PUBLIC |
| `/contact` | GET/POST | `contact` | PUBLIC |
| `/connexion` | GET/POST | `connexion` | PUBLIC |
| `/inscription` | GET/POST | `inscription` | PUBLIC |
| `/profil` | GET/POST | `profil` | ROLE_USER |
| `/logout` | GET | `logout` | ROLE_USER |

### Devis (`DevisController`)

| URL | Méthode | Route name | Auth |
|-----|---------|------------|------|
| `/devis` | GET/POST | `devis` | PUBLIC |

### Avis (`AvisController`)

| URL | Méthode | Route name | Auth |
|-----|---------|------------|------|
| `/vos-retours` | GET | `avis_liste` | PUBLIC |
| `/vos-retours/ajouter` | GET/POST | `avis_ajouter` | ROLE_USER |

### Admin (tous `ROLE_ADMIN`)

| Préfixe | Controller |
|---------|------------|
| `/admin` | DashboardController |
| `/admin/articles` | ArticleAdminController |
| `/admin/categories` | CategoryAdminController |
| `/admin/collections` | CollectionAdminController |
| `/admin/commandes` | CommandeAdminController |
| `/admin/devis` | DevisAdminController |
| `/admin/avis` | AvisAdminController |
| `/admin/messages` | MessageAdminController |
| `/admin/fournisseurs` | FournisseurAdminController |
| `/admin/caracteristiques` | CaracteristiqueAdminController |
| `/admin/templates` | TemplateAdminController |
| `/admin/uploads` | UploadAdminController |

---

## 5. Services

### CartService
Stockage en **session PHP** (clé `cart`).

```
addItem(article, quantity, variant?)
removeItem(itemId)
updateQuantity(itemId, quantity)
getCart() → array
getTotal() → float
getTotalQuantity() → int
clear()
isEmpty() → bool
```

Structure d'un item panier :
```json
{
  "item-id-123": {
    "article": { "id", "nom", "slug", "prix", "image" },
    "variant": { "id", "nom", "prix", "sku" },
    "quantity": 2
  }
}
```

### UploadService
- Répertoire : `public/uploads/`
- Formats acceptés : JPEG, PNG, GIF, WebP
- Taille max : 5 MB
- Redimensionnement auto via GD (max 1200×1200)

```
upload(file, directory?) → ?string
uploadMultiple(files[], directory?) → array
delete(path) → bool
resize(path, maxWidth?, maxHeight?) → bool
```

### MailerService
- From : `boutique.tdbr@gmail.com`
- Notifications : inscription, commande, contact, devis

### JWTService
- Algo : HS256 — Secret via `JWT_SECRET`
- Expiration : 86400s (24h)
- Utilisé pour authentification API (routes `/api/`)

---

## 6. Event Subscribers

### NavbarSubscriber
- Écoute `KernelEvents::REQUEST`
- Injecte les catégories actives dans `request._categories`
- Disponible dans tous les templates : `app.request.attributes.get('_categories')`

### JWTAuthenticationSubscriber
- Middleware auth JWT pour les routes `/api/`

---

## 7. Twig — Extensions et filtres

Définis dans `src/Twig/AppExtension.php` :

| Filtre | Usage |
|--------|-------|
| `\|price` | Formate en euros : `12,50 €` |
| `\|date_french` | Date FR : `22/02/2026 à 14:30` |
| `\|truncate` | Tronque à N caractères |
| `\|to_string` | Conversion robuste (BSON, arrays, objets) |

Fonction : `asset_exists(path)` → vérifie l'existence d'un asset.

---

## 8. Formulaires

| Classe | Page | Champs principaux |
|--------|------|-------------------|
| `CheckoutType` | `/checkout` | prenom, nom, email, telephone, adresse, codePostal, ville, pays (FR/BE/CH/LU/CA), notes, modePaiement (carte/virement/paypal) |
| `DevisType` | `/devis` | nom, email, telephone, concept, contexte, quantite (1-10/11-50/51-100/100+), moyenContact, supports (JSON), messageAdditionnel |
| `AvisType` | `/vos-retours/ajouter` | contenu, note (1-5), photo (file) |
| `ContactType` | `/contact` | nom, email, sujet, message |
| `LoginType` | `/connexion` | email, password |
| `RegistrationType` | `/inscription` | prenom, nom, email, password |

---

## 9. Sécurité

```yaml
# config/packages/security.yaml (résumé)
password_hasher: auto (bcrypt)
provider: entity User (via email)

firewall main:
  form_login: /connexion
  remember_me: 7 jours
  CSRF: activé

access_control:
  - /admin/*  → ROLE_ADMIN
  - /profil/* → ROLE_USER
  - /connexion, /inscription → PUBLIC_ACCESS
```

---

## 10. Base de données

**15 tables MySQL** :

```
users · categories · product_collections · articles · article_images
variantes · commandes · messages · fournisseurs · avis · devis
caracteristiques · caracteristique_valeurs · variante_templates
template_caracteristiques (join table) · doctrine_migration_versions
```

**Migrations Doctrine** (`migrations/`) :
- `Version20260217000000` — Tables initiales
- `Version20260219000000` — Devis, avis, messages
- `Version20260222000000` — Caractéristiques, templates
- `Version20260222200000` — Table fournisseurs + fournisseur_id sur articles

**Schéma complet** disponible dans `tdbr_schema.sql`.

---

## 11. Assets et build CSS/JS

> **Important** : le CSS est pré-compilé et purgé. Les nouvelles classes Tailwind non présentes dans le build ne s'affichent pas. Utiliser `npm run build` après toute modification de template ou utiliser des **styles inline** pour les ajouts ponctuels.

```bash
npm run dev       # Build dev avec watch
npm run build     # Build production (public/build/)
npm run dev-server # Webpack dev-server
```

Fichiers compilés dans `public/build/` :
- `app.a3414627.css` — Tailwind + styles TDBR
- `app.579e6744.js` — Alpine.js + scripts
- `runtime.f073a35a.js` · `705.31b65463.js` — Chunks webpack

---

## 12. Variables d'environnement

Fichier `.env` (valeurs par défaut dev) — créer `.env.local` en prod :

```env
APP_ENV=dev
APP_SECRET=...

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=app
DB_USER=app
DB_PASSWORD=!ChangeMe!
DB_VERSION=8.0

MAILER_DSN=null://null   # smtp://user:pass@host en prod
MAILER_FROM=boutique.tdbr@gmail.com

JWT_SECRET=change-this-in-production
JWT_EXPIRATION=86400

UPLOAD_DIR=%kernel.project_dir%/public/uploads
MAX_UPLOAD_SIZE=5242880
```

---

## 13. État du site (au 2026-02-22)

### Fonctionnel

- Catalogue complet (articles, catégories, collections, variantes)
- Panier en session + checkout (formulaire validé)
- Commandes (stockage JSON, statuts, gestion admin)
- Devis (formulaire public + gestion admin)
- Avis (dépôt utilisateur + modération admin)
- Contact (formulaire + notifications email)
- Authentification (inscription, connexion, profil, rôles)
- Admin panel CRUD complet
- Upload images (redimensionnement GD)
- Emails (inscription, commande, contact, devis)
- Partenaires (Printful, Vistaprint, Du fromage au dessert)

### Désactivé temporairement (site en construction)

- **Bouton "Procéder au paiement"** dans `panier.html.twig` → désactivé
- **Bouton "Valider la commande"** dans `checkout.html.twig` → désactivé
- **Bandeau amber** affiché sur toutes les pages (`base.html.twig`, styles inline)

### Pas encore implémenté

- Passerelle de paiement réelle (Stripe / PayPal)
- Tests unitaires (phpunit configuré mais vides)
- Rate limiting / protection anti-spam
- Caching avancé (Redis)

---

## 14. Déploiement cible (Hostinger)

Voir `DEPLOY-HOSTINGER.md` pour le détail complet.

```bash
# Préparer
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod

# Sur le serveur (SSH)
php bin/console doctrine:migrations:migrate --env=prod
php bin/console app:create-admin

# Créer .env.local avec credentials prod
# Vérifier permissions public/uploads/ (chmod 755)
```

Architecture visée :
- `tedybear.fr/` → application Symfony complète
- `tedybear.fr/uploads/` → images

---

## 15. Fichiers uploadés dans Git (forcés)

Les dossiers `uploads/` sont dans `.gitignore` sauf `.gitkeep`. Fichiers forcés via `git add -f` :

| Fichier | Emplacement |
|---------|-------------|
| `logo-printful.svg` | `public/uploads/fournisseurs/` |
| `vista.png` | `public/uploads/fournisseurs/` |
| `baptiste.jpg` | `public/uploads/general/` |
| `fromage_dessert.jpg` | `public/uploads/general/` |

---

## 16. Commandes utiles

```bash
# Démarrage
php -S localhost:8000 -t public

# Base de données
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:status

# Cache
php bin/console cache:clear

# Admin
php bin/console app:create-admin --email=... --password=...

# Assets
npm run build
npm run dev

# Tests
bash test-api.sh      # Linux/Mac
test-api.bat          # Windows
```

---

## 17. Contexte métier TDBR

**Thématiques produits** : Jeux de société (meeples, références gamer) · Fromage (humour gastronomique)

**Flux de création** :
Idée → IA générative (Gemini / Midjourney) → GIMP → Gigapixel → Outil fournisseur / Canva

**Fournisseurs** :
- **Printful** (Lituanie) — T-shirts, vêtements, merchandising
- **Vistaprint** (Europe) — Mugs, tote-bags, accessoires

**Dépôt-vente** :
- **"Du fromage au dessert"** — 19 rue des Jacobins, Orthez
  - Myriam (sœur) — pâtisserie "Un monde de pâtisserie"
  - Baptiste (beau-frère) — fromagerie d'Orthez
