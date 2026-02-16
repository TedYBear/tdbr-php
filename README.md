# TDBR - E-commerce Teddy Bears

Application e-commerce complète pour la vente de peluches artisanales.

## Stack Technique

- **Backend:** Symfony 6.3 + PHP 8.2
- **Frontend:** Twig + Tailwind CSS 3.4.19 + Alpine.js 3.13.3
- **Database:** MongoDB
- **Build:** Webpack Encore
- **Auth:** Symfony Security Component

## Installation

```bash
# Cloner le repo
git clone https://github.com/votre-username/tdbr-php.git
cd tdbr-php

# Installer les dépendances
composer install
npm install

# Configurer .env
cp .env .env.local
# Éditer .env.local avec vos paramètres MongoDB

# Build assets
npm run build

# Démarrer le serveur
php -S localhost:8000 -t public
```

## Fonctionnalités

### Frontend Public
- Catalogue produits avec filtres et pagination
- Page détail produit avec variantes
- Panier et processus de commande
- Authentification utilisateur
- Profil utilisateur avec historique commandes

### Interface Admin
- Dashboard avec statistiques
- Gestion articles (CRUD + variantes + images)
- Gestion catégories et collections
- Suivi des commandes
- Messages de contact

### Services
- Upload d'images avec redimensionnement
- Notifications email
- Génération de slugs
- Filtres Twig personnalisés

## Développement

```bash
# Watch mode pour le développement
npm run watch

# Build pour la production
npm run build
```

## Documentation

Voir [MIGRATION_RESUME.md](MIGRATION_RESUME.md) pour les détails complets de la migration Vue.js → Symfony.

## Licence

Propriétaire - TDBR © 2026
