# Audit de sécurité — 2026-06-10 (état d'avancement)

Suite de [SECURITY-AUDIT-2026-05-21.md](SECURITY-AUDIT-2026-05-21.md). App = monolithe
Symfony 6.3 + Twig + MySQL (Doctrine). Repo : github.com/TedYBear/tdbr-php.

## ✅ Phase 1 — FAIT (2026-06-10)

- **Fichiers sensibles supprimés** (tree + tout l'historique git, force-push) :
  `create_admin.php`, `test_password.php`, `debug_images.php`, `tdbr_data.sql`, `tdbr_schema.sql`.
- **Credential MongoDB exposé** (`tdbr_db_user` / mot de passe) purgé de tout l'historique via
  `git-filter-repo --replace-text`. ⚠️ Le mot de passe reste à considérer compromis →
  **cluster MongoDB Atlas à supprimer côté console** (MongoDB n'est plus utilisé par l'app).
- **Rate-limiting login** : `login_throttling` (5 essais / 15 min) dans `security.yaml`
  + dépendance `symfony/rate-limiter` ajoutée.
- **TLS Composer** réactivé (`secure-http=true`, `disable-tls=false`) dans la config globale.
- **Doc déploiement** réécrite (Git / Symfony+MySQL) + troubleshooting `ClassNotFoundError DebugBundle`.
- Sauvegarde git complète avant réécriture : `../tdbr-backup-20260610-232519.bundle`.

### Reste à faire côté infra (manuel, hors code)
- [ ] Supprimer le cluster MongoDB Atlas `tdbr.x5g60ng`.
- [ ] Vérifier que `.env.local` de prod a de **vrais** `APP_SECRET` et `JWT_SECRET` (pas les placeholders).

## ✅ Phase 2 — FAIT (2026-06-11)

- **Symfony 6.3 → 6.4.41 LTS** (maintenu jusqu'à 11/2026, EOL 11/2027).
  Toutes les contraintes `6.3.*` → `6.4.*` (composer.json + `extra.symfony.require`).
- **`firebase/php-jwt` 6.11 → 7.0.5** : corrige CVE-2025-45769. API inchangée
  (`JWTService` fonctionne tel quel, vérifié par smoke test encode/decode).
- **Autres dépendances** mises à jour dans leurs contraintes (doctrine-bundle 2.18,
  doctrine/orm 2.20.13, twig/extra-bundle 3.24, etc.).
- **`composer audit` : 0 vulnérabilité** (39 → 0).
- Vérifications : `lint:container`, `lint:twig` (96 fichiers), `lint:yaml`,
  `cache:warmup --env=prod` OK. (Pas de suite de tests dans le projet.)

### ⚠️ Au déploiement de la Phase 2
- php-jwt v7 **rejette les clés HMAC < 32 octets** (`DomainException: Provided key is too short`).
  Le `JWT_SECRET` de prod doit faire ≥ 32 caractères — avec la génération recommandée
  (`bin2hex(random_bytes(32))` = 64 hex) c'est bon, mais un secret court ferait planter
  la génération de tokens. À vérifier dans `.env.local` avant/juste après le déploiement.
- Séquence : `git pull` → `composer install --no-dev --optimize-autoloader` →
  `cache:clear --env=prod` + `cache:warmup --env=prod`.
- Tester ensuite : login, parcours panier/checkout, paiement Mollie (webhook), espace admin.
- Évaluer Symfony 7.x plus tard (6.4 LTS laisse le temps jusqu'à fin 2026).

## ⏳ Phase 3 — Durcissements (plus rapides)

- [ ] **CSP** : retirer `'unsafe-inline'` / `'unsafe-eval'` de `script-src`
  dans `SecurityHeadersSubscriber.php` (passer les scripts inline à des nonces).
- [ ] **Sous-système JWT legacy** : `JWTService` + `JWTAuthenticationSubscriber` comparent
  `role === 'admin'` (et non `ROLE_ADMIN`), HS256 avec secret potentiellement faible.
  Vérifier s'il est encore utilisé (routes `/api/*`) → supprimer si mort, sinon sécuriser.
- [ ] **CSRF** : tokens explicites sur routes POST/JSON sensibles
  (`/panier/*`, `/mon-depot/*`, `/proposition/{token}/*`). Aujourd'hui mitigé seulement
  par `cookie_samesite=strict`.
- [ ] **Politique mot de passe** : passer le minimum de 8 → 12 caractères + complexité
  (ex. `InvitationController`).

## Points déjà OK (vérifiés)
- Anti-IDOR dépôt-vente correct (`MonDepotController` vérifie l'ownership).
- Webhook Mollie acceptable (re-vérifie le paiement via l'API).
- `|raw` en Twig = uniquement `json_encode|raw` (sûr).
- Headers de sécurité, cookies httponly/samesite, CSRF formulaires : OK depuis l'audit de mai.
- Profiler / WebProfilerBundle : dev uniquement.
