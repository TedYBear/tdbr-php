# Changelog - 2026-05-26 à 2026-05-27

## 4 Fonctionnalités implémentées

### 1. ✅ Bouton "Envoyer vers Printful" (Admin)
- **Route** : `POST /admin/commandes/{id}/send-to-printful`
- **Action** : `CommandeAdminController::sendToPrintful()`
- **Comportement** :
  - Affiche un bouton dans la page de détail commande si la commande contient des articles Printful
  - Filtre les articles avec `printfulVariantId`
  - Crée un brouillon de commande sur Printful
  - Gère les erreurs avec flash messages
- **Commits** : `0adf3d8`

### 2. ✅ Affichage Avatar & Liens Auth sur Desktop (1024px+)
- **Fichiers modifiés** :
  - `templates/layout/navbar.html.twig` - Ajout d'IDs spécifiques et styles inline
  - `assets/app.js` - Fonction `updateDesktopMenuVisibility()` basée sur `window.innerWidth >= 1024`
  - `assets/styles/app.css` - Media query `@media (min-width: 1024px)` avec sélecteurs d'ID
- **Commits** : `76a91cb`, `12d2e04`, `7e2f5ed`

### 3. ✅ Correction Badge Panier (Centrage du nombre)
- **Problème** : Le chiffre n'était pas centré en viewport > 1020px
- **Root cause** : Règle CSS générique `nav .hidden { display: block !important; }` affectait le badge
- **Solution** :
  - Remplacement par sélecteurs spécifiques `#user-menu-desktop` et `#auth-links-desktop`
  - Styles inline pour le badge : `display: flex; align-items: center; justify-content: center;`
- **Commits** : `ca46422`, `3022de8`, `dc252ba`, `853c216`, `af446ac`, `dfa961e`

### 4. ✅ Changement Statut "en_cours" → "en_production"
- **Fichiers modifiés** :
  - `templates/admin/commandes/detail.html.twig`
  - `templates/admin/commandes/index.html.twig`
  - `templates/admin/devis/detail.html.twig`
  - `templates/admin/devis/_badge_statut.html.twig`
  - `templates/components/badge_status.html.twig`
  - `templates/auth/profil.html.twig`
  - `src/Service/MailerService.php`
- **Label** : "En cours" → "En production"
- **Commit** : `01f1f5e`

## Assets Compilés

- **CSS** : `app.76d8f153.css` (63KB)
- **JS Principal** : `app.6f8cc793.js` (803B)
- **JS Chunk** : `705.31b65463.js` (45KB)
- **Runtime** : `runtime.f073a35a.js`
- **Généré le** : 2026-05-26 15:06 (npm run build)

## Configuration Production

### Problème identifié
- Production server lancé en APP_ENV=dev sans DebugBundle disponible
- **Error** : `ClassNotFoundError: DebugBundle not found`

### Solution
- ✅ Créé `.env.prod.local` avec `APP_ENV=prod`
- À remplir avec credentials production :
  - JWT_SECRET
  - DB_PASSWORD
  - MAILER_DSN
  - MOLLIE_API_KEY
  - PRINTFUL_API_KEY

### Déploiement nécessaire
```
FTP → /public/build/
  ├── app.76d8f153.css
  ├── app.6f8cc793.js
  ├── 705.31b65463.js
  ├── runtime.f073a35a.js
  ├── manifest.json
  ├── entrypoints.json
  └── images/

FTP → / (racine)
  └── .env.prod.local (après remplissage des credentials)
```

## Prochaines étapes
1. Remplir `.env.prod.local` avec les vraies valeurs production
2. Uploader `.env.prod.local` via FTP à la racine
3. Uploader les assets du `/public/build/` à la même location sur prod
4. Tester les 4 fonctionnalités sur tedybear.fr
