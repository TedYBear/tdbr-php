# Audit de Sécurité — 2026-06-26

## Résumé

Analyse combinée **revue de code statique (Symfony)** + **tests passifs non intrusifs sur la production**.

Le code applicatif est **solide** (CSRF généralisé, Doctrine ORM sans SQLi, uploads durcis, webhook Mollie vérifié par rappel API, en-têtes de sécurité complets dans le code). Les problèmes identifiés étaient au niveau **déploiement / infrastructure** ; le plus grave (CSP à nonce neutralisée en prod, #1) **a été corrigé le 2026-06-26**.

**Cibles :** code du repo + live passif. Le site réel est **tedybear.fr** (Symfony, PHP 8.3.30, Hostinger). `tdbr.fr` n'héberge pas l'application (voir #2).

| # | Sévérité | Sujet | Statut |
|---|----------|-------|--------|
| 1 | ✅ Élevé | CSP à nonce écrasée par le serveur Hostinger | CORRIGÉ (hPanel « Forcer HTTPS » désactivé) |
| 2 | ⚪ Hors périmètre | tdbr.fr = domaine parqué + tracker russe sur PHP 7.4 EOL | NON APPLICABLE (domaine non possédé) |
| 3 | 🟡 Faible | Version PHP exposée (`X-Powered-By`) | À CORRIGER (php.ini) |
| 4 | 🟡 Faible | `www.tedybear.fr` → `/public/index.php` | À CORRIGER (hPanel) |
| 5 | 🟡 Faible | `public/uploads/` sans blocage d'exécution PHP | CORRIGÉ (.htaccess) |

---

## ✅ #1 — La CSP à nonce était écrasée par le serveur Hostinger (XSS) — CORRIGÉ

- **Constat :** `SecurityHeadersSubscriber` (`src/EventSubscriber/SecurityHeadersSubscriber.php:64`) pose une CSP stricte à nonce par requête (`script-src 'self' 'nonce-…'`). Mais le navigateur recevait en production uniquement :
  ```
  Content-Security-Policy: upgrade-insecure-requests
  ```
- **Diagnostic :** tous les autres en-têtes du subscriber arrivaient bien (HSTS, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy). Seule la CSP était remplacée. Le CDN a d'abord été suspecté (`Server: hcdn`), mais après l'avoir désactivé l'en-tête restait faible avec `Server: LiteSpeed` → la réécriture venait du **serveur d'origine**, pas de l'edge. Source réelle : la fonction **« Forcer HTTPS » de hPanel**, qui injecte `Content-Security-Policy: upgrade-insecure-requests` au niveau serveur et écrase celle de PHP.
- **Impact :** la mécanique de nonce (principale protection contre l'injection de scripts) n'avait aucun effet en prod. Un script injecté se serait exécuté sans contrainte.
- **Correctif (2026-06-26) :** désactivation du toggle **« Forcer HTTPS »** dans hPanel — redondant car `public/.htaccess` force déjà HTTPS (la redirection 301 fonctionne toujours). La CSP complète à nonce est désormais servie, et le nonce tourne bien à chaque requête (vérifié : 2 requêtes → 2 nonces distincts). Vérification : `curl -sI https://tedybear.fr | grep -i content-security`.

## ⚪ #2 — tdbr.fr : domaine parqué + tracker russe sur PHP 7.4 (EOL) — HORS PÉRIMÈTRE

- **Constat :** `tdbr.fr` et `www.tdbr.fr` renvoient sur **tous** les chemins une page de 378 octets contenant un compteur **LiveInternet (`counter.yadro.ru`)**, sous **PHP 7.4.33** (fin de vie).
- **Statut :** **NON APPLICABLE** — le domaine `tdbr.fr` n'appartient pas à TedYBear. Le site de production est **tedybear.fr** uniquement. Aucune action requise de notre côté ; conservé ici pour mémoire (ne pas confondre les deux domaines).

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
