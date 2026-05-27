# Système d'Analytiques de Trafic

## ✅ Implémenté (2026-05-27)

Un dashboard complet de monitoring de trafic a été ajouté à l'admin de TDBR site_v3.

### Fichiers créés (8 fichiers)

#### 1. **Entity** : `src/Entity/PageView.php`
- Table `page_views` avec 9 champs :
  - `routeName` : route Symfony (home, article_detail, etc.)
  - `entityType` : type d'entité (article, categorie, collection, null)
  - `entitySlug` : slug depuis l'URL
  - `entityId` : ID résolu depuis la DB
  - `sessionId` : fingerprint anonyme (SHA256)
  - `ipPartial` : 3 premiers octets IPv4 (GDPR)
  - `viewedAt`, `createdAt` : timestamps
- 3 index pour les requêtes analytiques

#### 2. **Repository** : `src/Repository/PageViewRepository.php`
- 8 méthodes d'agrégation :
  - `countViews()` : total vues
  - `countUniqueVisitors()` : visiteurs distincts
  - `topArticles()` : top 10 articles
  - `topPages()` : top 10 pages (routes)
  - `topCategories()` : top 5 catégories
  - `topCollections()` : top 5 collections
  - `viewsByDay()` : vues par jour (native SQL)
  - `mostViewedPageAllTime()` : page la plus vue

#### 3. **EventSubscriber** : `src/EventSubscriber/PageViewSubscriber.php`
- Écoute `KernelEvents::TERMINATE` (non-bloquant, après réponse envoyée)
- Guards : GET requests, 200 responses, routes whitelist
- Résout l'entité depuis slug → stocke entityId
- Génère fingerprint anonyme : `sha256(ip | UA | date)`
- Anonymise l'IP (3 premiers octets)
- Persist dans try/catch pour ne jamais casser l'app

#### 4. **Controller** : `src/Controller/Admin/AnalyticsAdminController.php`
- Route : `GET /admin/analytics` → `admin_analytics`
- Résolution période : today, 7j, 30j, all
- Hydrate les noms (1 findBy par type → pas de N+1)
- Passe 13 variables au template

#### 5. **Template** : `templates/admin/analytics/index.html.twig`
- Layout responsive (2 colonnes sur lg+)
- Filtres période via query string (bookmark-friendly)
- 4 KPI cards (total vues, visiteurs, vues today, page top)
- 4 tableaux top (10 articles, 10 pages, 5 catégories, 5 collections)
- Tableau vues par jour (30 lignes max)
- CSS classes existantes (.card, .divide-y, etc.)

#### 6. **Migration** : `migrations/Version20260527010000.php`
- Crée table page_views avec 3 index
- À exécuter sur le serveur : `php bin/console doctrine:migrations:migrate`

#### 7. **Sidebar** : `templates/admin/partials/sidebar.html.twig` (modifiée)
- Lien "Analytiques" ajouté entre Grilles de prix et séparateur
- Icône bar-chart Heroicons
- Active state : `starts with 'admin_analytics'`

### Architecture

**Tracking anonymisé** (GDPR-friendly, sans cookies) :
```
sessionId = sha256(ip | userAgent | 'Y-m-d')
ipPartial = '192.168.1' (3 octets, IPv6 → null)
```

**Routes trackées** (9 routes publiques whitelist) :
```
home, catalogue, presentation, avis_liste, devis, contact,
categorie, collection, article_detail
```

**Routes exclues** :
- Tout ce qui commence par `admin` ou `api`
- Réponses non-200
- Requêtes non-GET

### Vérifications ✓

```bash
# Route enregistrée
php bin/console debug:router admin_analytics
→ ✓ Retourne /admin/analytics

# Subscriber enregistré
php bin/console debug:event-dispatcher kernel.terminate
→ ✓ PageViewSubscriber::onKernelTerminate() présent

# Cache cleared
php bin/console cache:clear
→ ✓ Cache dev cleared

# Commit
git log -1
→ ✓ Feat: ajoute système d'analytiques de trafic
```

---

## 📋 À faire sur le serveur (tedybear.fr)

### 1. **Déployer le code** (via Git ou FTP)

Si Git :
```bash
git pull origin main
```

Si FTP : uploader les fichiers depuis `site_v3/` :
```
src/Entity/PageView.php
src/Repository/PageViewRepository.php
src/EventSubscriber/PageViewSubscriber.php
src/Controller/Admin/AnalyticsAdminController.php
templates/admin/analytics/index.html.twig
migrations/Version20260527010000.php
templates/admin/partials/sidebar.html.twig
```

### 2. **Exécuter la migration**

Via cPanel / SSH :
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Ou via phpMyAdmin : import SQL (si migration auto-exécution n'est pas possible) :
```sql
CREATE TABLE page_views (
    id          INT UNSIGNED AUTO_INCREMENT NOT NULL,
    route_name  VARCHAR(100)  NOT NULL,
    entity_type VARCHAR(50)   DEFAULT NULL,
    entity_slug VARCHAR(300)  DEFAULT NULL,
    entity_id   INT           DEFAULT NULL,
    session_id  VARCHAR(64)   NOT NULL,
    ip_partial  VARCHAR(50)   DEFAULT NULL,
    viewed_at   DATETIME      NOT NULL,
    created_at  DATETIME      NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

CREATE INDEX idx_page_views_viewed_at ON page_views (viewed_at);
CREATE INDEX idx_page_views_entity    ON page_views (entity_type, entity_slug);
CREATE INDEX idx_page_views_route     ON page_views (route_name);
```

### 3. **Nettoyer le cache** (si nécessaire)

```bash
php bin/console cache:clear --env=prod
```

### 4. **Test**

1. Accéder à `/admin/analytics`
2. Devrait afficher 4 cards vides (pas de données yet)
3. Visiter quelques pages publiques (home, article, catégorie)
4. Rafraîchir `/admin/analytics` → les données devraient apparaître

---

## 📊 Dashboard

### Filtres période
- Aujourd'hui → vues de 00:00 à 23:59
- 7 jours → les 6 derniers jours + aujourd'hui
- 30 jours → les 29 derniers jours + aujourd'hui
- Tout le temps → depuis 2000-01-01 (covers all data)

### KPI Cards (4)
1. **Total vues (période)** — COUNT(id) BETWEEN from AND to
2. **Visiteurs uniques (période)** — COUNT(DISTINCT sessionId) BETWEEN from AND to
3. **Vues aujourd'hui** — hard-coded today midnight to 23:59
4. **Page la plus vue** — across all time (top route)

### Tableaux
1. **Top 10 Articles** — groupBy entitySlug, limit 10
2. **Top 10 Pages** — groupBy routeName, limit 10
3. **Top 5 Catégories** — groupBy entitySlug WHERE entityType='categorie'
4. **Top 5 Collections** — groupBy entitySlug WHERE entityType='collection'
5. **Vues par jour** — GROUP BY DATE(viewed_at), limit 30, ORDER BY DESC

---

## 🔧 Maintenance future

### Croissance de la table
À ~1000 hits/jour → 365k rows/an → acceptable sur MySQL 8 + indexes

### Archivage (optionnel)
Créer une console command pour nettoyer les vieux hits :
```bash
DELETE FROM page_views WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
```

### Monitoring
Via cPanel phpMyAdmin :
- Vérifier taille table `page_views`
- Vérifier que les index sont utilisés
- Monitorer les perfs de la requête `viewsByDay()` (native SQL)

---

## 📝 Notes

- **Pas de dépendance externe** — pure Symfony + Doctrine
- **Pas de cookies** — utilise fingerprint anonyme
- **GDPR-friendly** — pas de PII stockée
- **Non-bloquant** — écrit en DB après réponse envoyée
- **Fail-safe** — si analytics crash, l'app continue normalement
- **Performant** — index couvrant toutes les requêtes d'admin

---

## Commit

```
238b687 Feat: ajoute système d'analytiques de trafic
```

Fichiers modifiés : 8  
Insertions : 906 lignes
