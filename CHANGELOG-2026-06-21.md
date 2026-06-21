# Changelog — 2026-06-18 à 2026-06-21

Session : personnalisation produit, refonte du design system (charte + polices),
uniformisation du code, et écran d'organisation du catalogue (drag-and-drop).

Branche de travail `test-design-system` mergée dans `main` (fast-forward, commit final `18313c0`).

---

## 1. 🛒 Demande de personnalisation produit (sur-mesure depuis la fiche)

Nouvelle fonctionnalité permettant à un client de demander une personnalisation
directement depuis une fiche produit, via une modale.

- **Bouton « Personnaliser ce produit »** sur la fiche produit, **visible uniquement
  si le template de l'article propose des personnalisations** ; **désactivé** si
  l'utilisateur n'est pas connecté (lien vers la connexion).
- **Modale Alpine** : menu déroulant **Type de demande** (alimenté par le template)
  + zone de **commentaire** → écran de confirmation.
- **Soumission AJAX** (`POST /demande-personnalisation`) : CSRF, honeypot, rate-limit,
  **réservée aux connectés**, demande **rattachée au compte**, coordonnées reprises du compte.
- La description enregistrée contient : nom de l'article + ID, type de demande, commentaire.
- **Espace compte** : section « Mes demandes sur-mesure » sur `/profil`.
- **Admin** (`/admin/devis`) : affichage source (fiche produit / devis), compte vs invité,
  lien produit, type de demande.
- **`/devis` (devis générique)** : rattachement au compte connecté + complétion des champs
  manquants du compte (sans écraser), honeypot + rate-limit.

**Modèle de données** — entité `DemandeSurMesure` étendue (`user`, `article`, `source`,
`personnalisation` JSON ; `concept` nullable). Migration `Version20260618010000`.

**Personnalisations gérées en admin par template :**
- Nouvelle entité **`TypePersonnalisation`** (nom, actif, ordre) + CRUD admin
  (`/admin/personnalisations`) + entrée sidebar.
- `VarianteTemplate` : relation **ManyToMany** vers les personnalisations proposables ;
  sélection dans le formulaire de template.
- Migration `Version20260619010000` (table `types_personnalisation` + jointure + seed).

**Anti-spam** : `config/packages/rate_limiter.yaml` (5 demandes / 10 min / IP).

**Commits** : `69783a2`, `2adf44f`, `be2866a`

---

## 2. 🎨 Refonte du design system

### Chargement des assets (fin des hash codés en dur)
- Fonction Twig **`encore_entry(entry, type)`** (lit `public/build/entrypoints.json`)
  dans `App\Twig\AppExtension`.
- Les 3 layouts (`base`, `admin/base_admin`, `mon_depot/base`) génèrent leurs `<link>`/`<script>`
  dynamiquement → **plus besoin de mettre à jour les hash CSS/JS à la main** après chaque build.
- Corrige au passage le CSS cassé côté admin (références figées vers des fichiers supprimés).

**Commits** : `85d49ac`, `251f7f5`, `fb49d5b`

### Classes réutilisables (uniformisation)
Extraction de motifs répétés (back-office / dépôt-vente) dans `assets/styles/app.css` :
- Boutons : **`.btn-dark`**, **`.btn-soft`**, **`.btn-success`**
- Modales : **`.modal-overlay`**, **`.modal-panel`**
- Champ compact : **`.form-control-sm`**
- Pastilles de stock en utilitaires Tailwind ; sélecteurs de variante alignés sur la marque.
- ~45 occasions de styles inline remplacées sur `mon_depot/index` et `admin/depot_vente/detail`.

**Commits** : `a72c554`, `092c330`, `9c390fb`, `e0d1ead`, `a18011b`

### Polices — Bricolage Grotesque + Hanken Grotesk, **auto-hébergées**
- Titres en **Bricolage Grotesque**, corps en **Hanken Grotesk** (remplace Space Grotesk / Inter).
- **woff2 variables auto-hébergés** (`assets/fonts/`, copiés dans `public/build/fonts/` par Webpack)
  via `@font-face` — **plus de dépendance Google Fonts CDN** (RGPD-friendly, compatible
  `font-src 'self'` de la CSP qui bloquait l'`@import` distant).

**Commits** : `c4ca0c0`, `c57464c`

### Charte chromatique — **Émeraude**
Palette finale (centralisée dans `tailwind.config.js` + `app.css`, propagée à tout le site,
emails et affiches inclus) :
- `primary #2F7A5B` · `secondary #4FB48A` · `accent #E7F4EE` · `dark #143027`
- `.btn-success` aligné sur le vert de marque (cohérence).

> Itérations intermédiaires explorées puis remplacées : Bordeaux Vineux (`1138317`),
> Verger (`4eacc14`). Source des chartes : projet **Claude Design** (`tokens.css`).

**Commits** : `1138317`, `4eacc14`, `9e3ec9c`, `639d268`

---

## 3. 🎛️ Organisation du catalogue (drag-and-drop)

Nouvel écran admin **`/admin/organisation`** : un seul écran hiérarchique pour réordonner
par **glisser-déposer** :
- **catégories** entre elles, **collections** dans leur catégorie, **articles** dans leur collection ;
- accordéon à 3 niveaux, listes triables imbriquées **indépendantes** (pas de déplacement entre parents) ;
- ordre **enregistré automatiquement** en AJAX (endpoints `reorder`, CSRF + `ROLE_ADMIN`).

- **Backend** : `OrganisationAdminController` (page + 3 endpoints `reorder`, `ordre = position`).
- **Front** : `app.js` importe **SortableJS** et initialise les listes `[data-sortable]`.
- **Tri public** : la page collection et le catalogue trient désormais les éléments par `ordre`
  (les articles d'une collection suivent enfin l'ordre défini en admin).
- **Dépendances ajoutées** : `sortablejs`, `core-js` (polyfills requis par Babel `useBuiltIns`).
- `meta csrf-token` ajouté au layout admin.
- **Pas de migration** (les champs `ordre` existaient déjà sur les 3 entités).

**Commit** : `18313c0`

---

## 4. 🖼️ Divers

- **Hero page collection** : suppression de l'animation (dégradé en boucle + particules
  flottantes) au profit du même fond statique que les pages catégorie
  (`bg-gradient-to-br from-accent to-white`). **Commit** : `78ddb17`

---

## 📦 Déploiement

```bash
git checkout main && git pull
php bin/console doctrine:migrations:migrate --no-interaction   # migrations perso/personnalisations si absentes
php bin/console cache:clear --env=prod
```

- **Aucune migration** ajoutée par le design system / les polices / l'organisation.
  Migrations en jeu : `Version20260618010000`, `Version20260619010000` (features perso).
- **Pas de `npm` requis sur le serveur** : `public/build/` (assets + polices + SortableJS) est versionné.

## ✅ À vérifier en QA
- Modale de personnalisation (connecté / non connecté) + « Mes demandes » + admin devis.
- Rendu Émeraude + polices (titres/corps) sur le site public et les emails.
- `/admin/organisation` : glisser-déposer aux 3 niveaux, persistance après rechargement.
