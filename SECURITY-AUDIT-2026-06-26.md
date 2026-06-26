# Audit de Sécurité — 2026-06-26

## Résumé

Analyse combinée **revue de code statique (Symfony)** + **tests passifs non intrusifs sur la production**.

Le code applicatif est **solide** (CSRF généralisé, Doctrine ORM sans SQLi, uploads durcis, webhook Mollie vérifié par rappel API, en-têtes de sécurité complets dans le code). Les problèmes identifiés sont au niveau **déploiement / infrastructure**, dont un qui **neutralise la principale défense XSS en production**.

**Cibles :** code du repo + live passif. Le site réel est **tedybear.fr** (Symfony, PHP 8.3.30, Hostinger). `tdbr.fr` n'héberge pas l'application (voir #2).

| # | Sévérité | Sujet | Statut |
|---|----------|-------|--------|
| 1 | 🔴 Élevé | CSP à nonce écrasée par l'edge Hostinger | À CORRIGER (hPanel) |
| 2 | 🟠 Moyen | tdbr.fr = domaine parqué + tracker russe sur PHP 7.4 EOL | À CORRIGER (DNS/hosting) |
| 3 | 🟡 Faible | Version PHP exposée (`X-Powered-By`) | À CORRIGER (php.ini) |
| 4 | 🟡 Faible | `www.tedybear.fr` → `/public/index.php` | À CORRIGER (hPanel) |
| 5 | 🟡 Faible | `public/uploads/` sans blocage d'exécution PHP | CORRIGÉ (.htaccess) |

---

## 🔴 #1 — La CSP à nonce est écrasée par l'edge Hostinger (XSS)

- **Constat :** `SecurityHeadersSubscriber` (`src/EventSubscriber/SecurityHeadersSubscriber.php:64`) pose une CSP stricte à nonce par requête (`script-src 'self' 'nonce-…'`). Mais le navigateur reçoit en production uniquement :
  ```
  Content-Security-Policy: upgrade-insecure-requests
  ```
- **Diagnostic :** tous les autres en-têtes du subscriber arrivent bien (HSTS, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy). Seule la CSP est remplacée. Rien dans le repo ne pose `upgrade-insecure-requests` → c'est l'edge Hostinger (`Server: hcdn`, `panel: hpanel`) qui l'injecte et écrase celle de l'application.
- **Impact :** la mécanique de nonce (principale protection contre l'injection de scripts) n'a aucun effet en prod. Un script injecté s'exécuterait sans contrainte.
- **Action :** dans hPanel, désactiver l'en-tête CSP / « security headers » auto-injecté par Hostinger (ou le configurer pour ne pas toucher à CSP), puis vérifier que la CSP complète sort bien (`curl -sI https://tedybear.fr | grep -i content-security`).

## 🟠 #2 — tdbr.fr : domaine parqué + tracker russe sur PHP 7.4 (EOL)

- **Constat :** `tdbr.fr` et `www.tdbr.fr` renvoient sur **tous** les chemins une page de 378 octets contenant un compteur **LiveInternet (`counter.yadro.ru`)**, sous **PHP 7.4.33** (fin de vie, plus de correctifs de sécurité depuis fin 2022).
- **Impact :** fuite vers un tiers (referer / URL / titre / résolution écran des visiteurs), page parasite sur un domaine de marque, runtime PHP non maintenu.
- **Action :** rediriger `tdbr.fr` → `tedybear.fr` (301), ou purger le parking et déployer/fermer proprement le domaine.

## 🟡 #3 — Version PHP exposée

- **Constat :** `X-Powered-By: PHP/8.3.30` sur les deux domaines.
- **Action :** `expose_php = Off` (php.ini ou `.user.ini`).

## 🟡 #4 — Redirection www non canonique

- **Constat :** `www.tedybear.fr` redirige (301) vers `https://tedybear.fr/public/index.php` — fuite du chemin interne `/public/` et URL non canonique. Le `.htaccess` du repo gère pourtant proprement www→non-www ; cette redirection vient d'une règle hPanel séparée.
- **Action :** corriger la redirection panel pour viser `https://tedybear.fr/`.

## 🟡 #5 — uploads/ sans blocage d'exécution PHP

- **Constat :** `public/uploads/` ne désactivait pas l'exécution PHP. Risque pratique faible (upload *admin-only*, ≤ 5 Mo, allowlist MIME, extension dérivée serveur — `src/Controller/Api/UploadController.php:35-70`), mais défense en profondeur manquante.
- **Statut :** **CORRIGÉ** — ajout de `public/uploads/.htaccess` désactivant l'exécution de scripts.

---

## ✅ Points solides (vérifiés)

- **CSRF** vérifié partout : AJAX admin (`X-CSRF-Token`) et formulaires publics (`PublicController`, `MonDepotController`, `DemandeSurMesureController`).
- **Pas de SQLi** : Doctrine ORM, aucun `->query()` concaténé, aucun `eval` / `system` / `unserialize`.
- **Cookie de session** : `secure; httponly; samesite=strict`.
- **Webhook Mollie** vérifié par rappel API (`getPayment()->isPaid()`), pas de confiance au POST.
- **En-têtes** présents en prod : HSTS (`includeSubDomains`, 1 an), X-Frame-Options DENY, nosniff, Referrer-Policy, Permissions-Policy.
- **Fichiers sensibles protégés** sur tedybear.fr : `.env` 404, `.git` 403, `composer.json` 404, `_profiler` 404.
- **Aucun secret committé** : `.env` versionné = template (placeholders) ; vrais secrets en `.env.local` / `.env.prod.local` (hors git).

## Dette résiduelle (à traiter après #1)

- CSP code : `'unsafe-eval'` (requis par Alpine.js build standard) et `script-src-attr 'unsafe-inline'` (handlers `onclick=` legacy). À retirer après migration vers `@alpinejs/csp` et handlers Alpine. Sans effet tant que #1 n'est pas réglé.
- HSTS : envisager l'ajout de `preload` + soumission à hstspreload.org.
