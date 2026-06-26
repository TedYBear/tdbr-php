# Audit de Sécurité — 2026-06-26

## Résumé

Analyse combinée **revue de code statique (Symfony)** + **tests passifs non intrusifs sur la production**.

Le code applicatif est **solide** (CSRF généralisé, Doctrine ORM sans SQLi, uploads durcis, webhook Mollie vérifié par rappel API, en-têtes de sécurité complets dans le code). Les problèmes identifiés étaient au niveau **déploiement / infrastructure** ; le plus grave (CSP à nonce neutralisée en prod, #1) **a été corrigé le 2026-06-26**. La **dette CSP** qui restait au niveau code (`'unsafe-eval'` + `script-src-attr 'unsafe-inline'`) a également été **entièrement soldée le 2026-06-26** : CSP finale `script-src 'self' 'nonce-…'` sans aucun `unsafe-*` (voir section dédiée).

**Cibles :** code du repo + live passif. Le site réel est **tedybear.fr** (Symfony, PHP 8.3.30, Hostinger). `tdbr.fr` n'héberge pas l'application (voir #2).

| # | Sévérité | Sujet | Statut |
|---|----------|-------|--------|
| 1 | ✅ Élevé | CSP à nonce écrasée par le serveur Hostinger | CORRIGÉ (hPanel « Forcer HTTPS » désactivé) |
| 2 | ⚪ Hors périmètre | tdbr.fr = domaine parqué + tracker russe sur PHP 7.4 EOL | NON APPLICABLE (domaine non possédé) |
| 3 | ✅ Faible | Version PHP exposée (`X-Powered-By`) | CORRIGÉ (live confirmé) |
| 4 | ✅ Faible | Redirection exposant `/public/index.php` | CORRIGÉ (live confirmé — purger le cache LiteSpeed pour le résidu www) |
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

## 🟡 #3 — Version PHP exposée — CORRIGÉ (après déploiement)

- **Constat :** `X-Powered-By: PHP/8.3.30` exposé en prod.
- **Correctif :** `expose_php` étant `PHP_INI_SYSTEM` (non modifiable via `.user.ini` sur mutualisé), l'en-tête est retiré dans `public/.htaccess` (`Header always unset X-Powered-By`). Effectif après `git pull` en prod. Vérif : `curl -sI https://tedybear.fr | grep -i x-powered` (doit ne rien renvoyer).

## 🟡 #4 — Redirection exposant `/public/index.php` — CORRIGÉ (après déploiement)

- **Constat :** la redirection HTTP→HTTPS renvoyait vers `https://tedybear.fr/public/index.php`, exposant le chemin interne `/public/`.
- **Cause :** sur Hostinger le document root est `public_html/` (le projet y est déployé, point d'entrée réel `public_html/public/index.php`). Le `.htaccess` racine réécrit en interne vers `public/`, *puis* la règle « Forcer HTTPS » de `public/.htaccess` se déclenchait sur l'URI déjà réécrite (`/public/index.php`) et la renvoyait dans le `Location`.
- **Correctif :** déplacement du forçage HTTPS + non-www dans le `.htaccess` racine, **avant** la réécriture vers `/public`, donc sur l'URI d'origine. La redirection produit désormais une URL propre (`https://tedybear.fr/…`). Effectif après `git pull` en prod. Vérif : `curl -sI http://tedybear.fr/ | grep -i location`.

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

## ✅ Dette CSP — SOLDÉE (2026-06-26)

La dette CSP identifiée après #1 (`'unsafe-eval'` + `script-src-attr 'unsafe-inline'`) a été **entièrement résorbée**. CSP finale servie :

```
script-src 'self' 'nonce-{aléatoire-par-requête}'
```

→ plus **aucun** `unsafe-*` côté scripts. La mécanique de nonce est désormais la seule autorité d'exécution des scripts.

- **Half A — retrait de `script-src-attr 'unsafe-inline'`** : les ~45 gestionnaires inline (`onclick=`/`onsubmit=`/`onchange=`/`oninput=`/`onmouseover=`) ont été migrés vers une **délégation déclarative par `data-*`** centralisée dans `assets/interactions.js` (`data-confirm`, `data-autosubmit`, `data-toggle-password`, `data-copy`, `data-qty-step`, `data-toast-close`, …).
- **Half B — retrait de `'unsafe-eval'`** : bascule du build Alpine standard vers **`@alpinejs/csp`** (évaluateur restreint, sans `eval` / `new Function`). Les composants à méthodes/getters ont été extraits des `x-data` inline vers `Alpine.data()` ; les expressions de directive accédant à des globales (`Math`, `parseInt`, `Object.keys/entries`, `window`…), les fonctions fléchées, le `??` et les écritures DOM ont été remplacés par des méthodes/getters ou des liaisons réactives.
- **En-tête** : `SecurityHeadersSubscriber` ne pose plus que `script-src 'self' 'nonce-…'`.

**Validation** : build OK, `lint:twig` OK, parsing des 469 expressions de directive par l'évaluateur CSP OK, et **QA navigateur complète** (form article, grille de prix, caractéristiques, modale de vente dépôt, dialogues de confirmation, bascule mot de passe, accordéon admin) — aucune violation CSP ni `Alpine Expression Error`. Mergé sur `main` le 2026-06-26.

## ✅ HSTS preload (2026-06-26)

- Directive **`preload`** ajoutée à l'en-tête HSTS (`max-age=31536000; includeSubDomains; preload`) dans `SecurityHeadersSubscriber`.
- Sous-domaines vérifiés HTTPS-ready avant l'engagement : `preprod.tedybear.fr` → HTTPS 200 + redirection HTTP→HTTPS 301 ; apex `tedybear.fr` → HTTPS 200.
- **Étape manuelle restante (après déploiement)** : soumettre `tedybear.fr` sur https://hstspreload.org/ — l'outil vérifie que l'en-tête live contient bien `preload`.
- ⚠ Engagement : une fois sur la liste preload, **tous** les sous-domaines doivent rester en HTTPS valide ; le retrait prend plusieurs mois. Ne pas créer de sous-domaine en HTTP nu.

## Dette résiduelle

- _(aucune)_ — voir l'étape manuelle de soumission HSTS preload ci-dessus.
