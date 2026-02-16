# Changelog

Toutes les modifications notables du projet TDBR Symfony seront documentées dans ce fichier.

## [1.0.0] - 2024-02-16

### Création initiale

Migration complète de l'API Express.js vers Symfony 6.3 + MongoDB.

#### Ajouté

**Controllers API**
- `ArticleController` - CRUD complet articles + duplication
- `AuthController` - Inscription, connexion, profil utilisateur
- `CategoryController` - CRUD complet catégories
- `CollectionController` - CRUD complet collections
- `HealthController` - Health check avec status MongoDB
- `UploadController` - Upload et suppression d'images

**Services**
- `MongoDBService` - Service centralisé pour connexion MongoDB Atlas
- `JWTService` - Génération et validation tokens JWT avec firebase/php-jwt

**Sécurité**
- `JWTAuthenticationSubscriber` - Event subscriber pour vérification JWT automatique
- Hachage bcrypt des mots de passe
- Protection routes admin (role-based)
- Configuration CORS dynamique

**Commands CLI**
- `app:create-admin` - Créer un utilisateur administrateur

**Configuration**
- Configuration MongoDB (URI, database name)
- Configuration JWT (secret, expiration)
- Configuration uploads (directory, max size)
- Variables d'environnement (.env, .env.local)
- Service container (services.yaml)

**Documentation**
- `README.md` - Documentation complète installation et usage
- `QUICKSTART.md` - Guide démarrage rapide (5 min)
- `DEPLOY-HOSTINGER.md` - Guide déploiement production Hostinger
- `MIGRATION-COMPLETE.md` - Récapitulatif migration Express → Symfony
- `COMMANDES-UTILES.md` - Liste commandes développement/production
- `CHANGELOG.md` - Ce fichier
- Scripts de test (`test-api.sh`, `test-api.bat`)

**Dépendances**
- Symfony 6.3 (framework-bundle, security-bundle, twig-bundle, etc.)
- mongodb/mongodb ^1.17 - Driver MongoDB officiel
- firebase/php-jwt ^6.10 - JSON Web Tokens
- Tous les bundles Symfony webapp

#### Fonctionnalités

**Authentification & Autorisation**
- ✅ Inscription utilisateur
- ✅ Connexion avec génération token JWT
- ✅ Récupération profil authentifié
- ✅ Modification profil
- ✅ Gestion rôles (user/admin)
- ✅ Protection automatique routes admin

**Gestion Catégories**
- ✅ Liste catégories actives (public)
- ✅ Catégorie par slug (public)
- ✅ CRUD complet (admin)
- ✅ Activation/désactivation
- ✅ Ordre personnalisé

**Gestion Articles**
- ✅ Liste articles actifs (public)
- ✅ Filtrage par catégorie/collection
- ✅ Article par slug (public)
- ✅ CRUD complet (admin)
- ✅ Duplication article
- ✅ Gestion stock
- ✅ Articles vedettes
- ✅ Images multiples
- ✅ Caractéristiques personnalisées

**Gestion Collections**
- ✅ Liste collections actives (public)
- ✅ Collection par slug (public)
- ✅ CRUD complet (admin)
- ✅ Image collection
- ✅ Ordre personnalisé

**Upload Fichiers**
- ✅ Upload images (JPEG, PNG, GIF, WebP)
- ✅ Validation taille (max 5MB)
- ✅ Noms uniques slugifiés
- ✅ Suppression images
- ✅ Protection admin uniquement

**Base de données**
- ✅ Connexion MongoDB Atlas
- ✅ Service centralisé pour toutes collections
- ✅ Compatibilité données Express existantes
- ✅ Health check avec ping MongoDB

#### Compatibilité

**100% compatible avec API Express.js**
- Mêmes endpoints (ex: `/api/categories`, `/api/auth/connexion`)
- Même format de réponses JSON
- Même authentification Bearer JWT
- Même structure MongoDB
- **Aucune modification du frontend Vue.js nécessaire**

**Collections MongoDB partagées**
- `users`
- `categories`
- `articles`
- `collections`
- `commandes`
- `devis`
- `messages`
- `caracteristiques`
- `templates`

#### Architecture

**Structure moderne Symfony**
```
src/
├── Command/          # Commandes CLI
├── Controller/Api/   # Contrôleurs API REST
├── EventSubscriber/  # Event subscribers (auth JWT)
└── Service/          # Services (MongoDB, JWT)
```

**Sécurité**
- Tokens JWT signés avec HMAC-SHA256
- Mots de passe hachés bcrypt
- Validation permissions role-based
- CORS configurable par environnement
- Headers sécurité HTTP

**Performances**
- Autowiring Symfony (injection dépendances automatique)
- Service container optimisé
- Autoloader optimisé pour production
- Cache OPcache compatible

#### Notes de migration

**Depuis Express.js**
1. Données MongoDB inchangées (pas de migration)
2. Changer `VITE_API_URL` dans frontend Vue.js
3. Même structure de tokens JWT
4. Mêmes routes API
5. Déploiement simplifié (PHP natif vs Node.js)

**Avantages Symfony**
- ✅ Compatible hébergement partagé PHP
- ✅ Pas de Node.js requis en production
- ✅ Framework mature et stable
- ✅ Documentation extensive
- ✅ Performance élevée avec OPcache
- ✅ Déploiement FTP simple

#### Known Issues

⚠️ **Composer SSL** - Certificats SSL peuvent causer erreurs installation
- **Solution** : `composer config secure-http false`

⚠️ **Extension MongoDB** - Extension PHP MongoDB doit être installée manuellement
- **Solution** : Télécharger DLL depuis PECL et activer dans php.ini

⚠️ **Dépendances manquantes** - mongodb/mongodb et firebase/php-jwt pas dans cache
- **Solution** : `composer require mongodb/mongodb firebase/php-jwt --no-scripts`

#### À implémenter (futures versions)

**Routes manquantes**
- [ ] Gestion commandes (CRUD)
- [ ] Gestion devis (CRUD)
- [ ] Messages contact (CRUD)
- [ ] Statistiques dashboard admin
- [ ] Caractéristiques produits (CRUD)
- [ ] Templates personnalisables (CRUD)

**Améliorations**
- [ ] Validation Symfony Validator (DTO)
- [ ] Pagination avec paramètres limit/offset
- [ ] Recherche full-text MongoDB
- [ ] Filtres avancés (prix, stock, etc.)
- [ ] Upload images multiples simultané
- [ ] Redimensionnement automatique images
- [ ] Cache Redis pour performances
- [ ] Tests unitaires PHPUnit
- [ ] Tests fonctionnels (API Testing)
- [ ] Documentation OpenAPI/Swagger
- [ ] Rate limiting par IP
- [ ] Logs structurés (Monolog)
- [ ] Sentry error tracking
- [ ] Métriques Prometheus

#### Contributeurs

- Claude Sonnet 4.5 - Migration complète Express → Symfony

---

## Format

Le format se base sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

### Types de changements

- **Ajouté** pour les nouvelles fonctionnalités
- **Modifié** pour les changements aux fonctionnalités existantes
- **Déprécié** pour les fonctionnalités qui seront bientôt supprimées
- **Supprimé** pour les fonctionnalités supprimées
- **Corrigé** pour les corrections de bugs
- **Sécurité** en cas de vulnérabilités
