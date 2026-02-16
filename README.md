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
- **Framework :** Symfony 6.3
- **PHP :** 8.2
- **Base de données :** MongoDB (extension PHP MongoDB 2.2.1)
- **Authentification :** Symfony Security Component (sessions PHP)
- **Services :**
  - UploadService (GD Library pour redimensionnement images)
  - MailerService (Symfony Mailer)
  - CartService (gestion panier en session)
  - SlugifyService

### Frontend
- **Templates :** Twig
- **CSS :** Tailwind CSS 3.4.19 (configuration custom TDBR)
- **JavaScript :** Alpine.js 3.13.3
- **Build :** Webpack Encore 4.x
- **Fonts :** Inter (sans-serif), Space Grotesk (headings)

### Couleurs Custom Tailwind
```js
colors: {
  primary: '#8B7355',    // Marron chaud
  secondary: '#D4AF7A',  // Or doux
  accent: '#F5E6D3',     // Beige clair
  dark: '#2C2416'        // Marron foncé
}
```

## 📦 Installation

### Prérequis
- PHP 8.2+ avec extensions : `mongodb`, `gd`, `intl`
- Composer 2.x
- Node.js 18+ et npm
- MongoDB 6.0+ (serveur local ou distant)

### Installation Locale

```bash
# Cloner le repo
git clone https://github.com/votre-username/tdbr-php.git
cd tdbr-php

# Installer dépendances PHP
composer install

# Installer dépendances Node
npm install

# Configuration environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres MongoDB

# Build assets
npm run build

# Démarrer le serveur
php -S localhost:8000 -t public
```

### Configuration MongoDB

Dans `.env.local` :
```env
MONGODB_URL=mongodb://localhost:27017
MONGODB_DB=tdbr
```

**Collections requises :**
- `articles` - Produits avec variantes et images
- `categories` - Catégories de produits
- `collections` - Collections thématiques
- `utilisateurs` - Comptes utilisateurs
- `commandes` - Commandes clients
- `messages` - Messages de contact
- `devis` - Demandes de devis
- `templates` - Templates personnalisables
- `caracteristiques` - Caractéristiques produits

## 🚀 Fonctionnalités

### Pages Publiques
- **Home** - Hero avec goodies personnalisés, features, thématiques
- **Catalogue** - Grille produits avec filtres catégories/collections et pagination
- **Article** - Détail produit avec galerie images, sélection variantes, ajout panier
- **Panier** - Gestion quantités, modification, suppression
- **Checkout** - Formulaire commande multi-sections (client, livraison, paiement)
- **Contact** - Formulaire avec sidebar informations
- **Présentation** - Profil TedYBear/Manu avec section IA & Transparence

### Authentification
- **Inscription** - Création compte avec validation email
- **Connexion** - Login avec "Remember me" (session 7 jours)
- **Profil** - Affichage/modification profil + historique commandes
- **Protection :** Sessions PHP sécurisées, bcrypt pour mots de passe

### Interface Admin (Role ROLE_ADMIN)
- **Dashboard** - Stats (articles, catégories, commandes, messages), dernières commandes
- **Articles** - CRUD complet avec variantes, upload images, duplication
- **Catégories** - Gestion catégories
- **Collections** - Gestion collections
- **Commandes** - Liste, détail, changement statut (avec notification email)
- **Messages** - Lecture, marquer lu, suppression
- **Devis** - Suivi demandes de devis
- **Templates** - Templates personnalisables
- **Caractéristiques** - Caractéristiques produits

### Services Intégrés

#### Upload d'Images
- **Validation :** JPG, PNG, GIF, WebP (max 5MB)
- **Redimensionnement :** Automatique à 1200x1200px
- **Préservation :** Transparence PNG/GIF
- **Routes :** `/admin/upload/image`, `/admin/upload/images`, `/admin/upload/delete`

#### Notifications Email
- **Registration** - Email bienvenue
- **Order Confirmation** - Récapitulatif commande complet
- **Order Status** - Notification changement statut
- **Contact Notification** - Alerte admin nouveau message
- **Contact Reply** - Réponse manuelle à message

**Templates email :** Design responsive avec gradient TDBR

#### Filtres Twig Custom
- `price` - Format prix français (1 234,56 €)
- `date_french` - Format date français (dd/mm/YYYY à HH:ii)
- `truncate` - Tronquer texte avec suffix

## ⚠️ Points d'Attention

### Sécurité
- **CSRF Protection :** Actif sur tous les formulaires
- **Uploads :** Validation stricte MIME types + taille
- **Passwords :** Hashés avec bcrypt via Symfony PasswordHasher
- **Sessions :** Configurées avec lifetime 7 jours (remember_me)
- **Admin :** Routes protégées par `#[IsGranted('ROLE_ADMIN')]`

### Performance
- **Assets :** Webpack génère fichiers hashés pour cache-busting
- **Images :** Redimensionnement automatique avant upload
- **MongoDB :** Indexer `slug`, `email`, `numero_commande` pour performances
- **Build :** `npm run build` avant déploiement production

### Configuration Production

**Mailer (dans `.env.local`) :**
```env
# Gmail
MAILER_DSN=gmail://username:password@default

# SMTP générique
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# Développement (fichiers .eml)
MAILER_DSN=null://null
```

**Permissions Fichiers :**
```bash
chmod -R 755 public/uploads
chmod -R 775 var/
```

**Webpack Production :**
```bash
npm run build
# Vérifie public/build/manifest.json généré
```

### Données Initiales

**Créer un admin :**
```bash
# Via MongoDB shell ou Compass
db.utilisateurs.insertOne({
  email: "admin@tdbr.fr",
  password: "$2y$13$...", // Hash bcrypt du mot de passe
  roles: ["ROLE_ADMIN"],
  prenom: "Admin",
  nom: "TDBR",
  createdAt: new ISODate()
})
```

**Créer catégories initiales :**
- "Jeux de Société" (slug: `jeux-de-societe`)
- "Fromage" (slug: `fromage`)

### Déploiement

**Hostinger recommandé :**
- Support PHP 8.2+ ✓
- MongoDB Atlas (gratuit tier 512MB) ✓
- Webpack build en local, upload `public/build/` ✓

**Checklist déploiement :**
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm run build` (fichiers dans `public/build/`)
- [ ] Configuration `.env.local` production
- [ ] `MAILER_DSN` configuré avec SMTP réel
- [ ] Permissions `public/uploads/` (755)
- [ ] Variables d'environnement serveur
- [ ] Tester routes publiques + admin

## 📚 Documentation Complémentaire

- **[PROFIL_TDBR.md](PROFIL_TDBR.md)** - Identité de marque et contexte personnel
- **[MIGRATION_RESUME.md](MIGRATION_RESUME.md)** - Résumé technique migration (8 phases)
- **[NOUVELLES_FEATURES.md](NOUVELLES_FEATURES.md)** - Upload images et emails (API, exemples)

## 🧑‍💻 Développement

```bash
# Watch mode (hot reload)
npm run watch

# Build production
npm run build

# Serveur dev PHP
php -S localhost:8000 -t public
```

## 📄 Licence

Propriétaire - TDBR © 2026
Créé par TedYBear (Emmanuel)
