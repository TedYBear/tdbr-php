# 🎯 Status du projet TDBR

**Date** : 21 juin 2026
**Stack** : Symfony 6.4 LTS · PHP 8.2 · MySQL 8 (Doctrine ORM) · Twig · Tailwind 3.4 · Alpine.js · Webpack Encore
**Status** : ✅ En production (site e-commerce complet)

> Historique détaillé : voir [CHANGELOG.md](CHANGELOG.md) et les changelogs datés `CHANGELOG-YYYY-MM-DD.md`.

---

## ✅ Opérationnel

### Boutique
- Catalogue → Catégorie → Collection → Article (galerie, variantes, prix dégressifs)
- Panier + Checkout + **paiement Mollie** (webhook), codes réduction
- Avis clients, contact, présentation
- Tri des catégories/collections/articles par `ordre`

### Comptes & sur-mesure
- Inscription / connexion (sessions, bcrypt), profil + historique
- Création de compte automatique après paiement (invités)
- **Demande de personnalisation produit** (modale fiche produit, rattachée au compte)
- **Sur-mesure / devis** générique + propositions commerciales
- **Dépôt-vente** partenaires (`/mon-depot`)

### Admin
- Dashboard + **analytiques de trafic**
- Articles, Catégories, Collections, Templates, Caractéristiques, **Personnalisations**
- **Organisation du catalogue** (drag-and-drop catégories/collections/articles)
- Commandes, Sur-mesure, Messages, Avis, Fournisseurs, Codes réduction, Grilles de prix,
  Boutiques relais, Dépôt-vente, Propositions, Marketing, Printful, Utilisateurs, Config site

### Design system
- Charte **Émeraude** + polices **Bricolage Grotesque / Hanken Grotesk** (auto-hébergées)
- Classes réutilisables (`.btn-*`, `.modal-*`, `.form-control-sm`), assets via `entrypoints.json`
- Projet **Claude Design** synchronisé (`tokens.css` + cartes d'aperçu)

### Sécurité
- CSP avec nonces, CSRF (formulaires + AJAX), rate-limiter (login + sur-mesure), honeypots
- Headers durcis (HSTS, X-Frame-Options, nosniff)

---

## 🔜 Pistes / à surveiller
- QA visuelle de l'écran `/admin/organisation` (drag-and-drop) après déploiement.
- Migration éventuelle vers `@alpinejs/csp` pour retirer `unsafe-eval` de la CSP.
- Couleurs sémantiques (success/warning/danger/info) du `tokens.css` non encore adoptées (statuts en Tailwind par défaut).
