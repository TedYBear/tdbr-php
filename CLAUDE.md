# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

TDBR — e-commerce site for personalized goodies (mugs, t-shirts, tote bags) by **TedYBear** (Emmanuel/Manu), themes "Jeux de Société" and "Fromage". Codebase, UI, docs and commit messages are in **French** — match that language.

Stack: **Symfony 6.4 LTS · PHP 8.1+ (targets 8.2) · MySQL 8 (Doctrine ORM + Migrations) · Twig · Tailwind 3.4 · Alpine.js · Webpack Encore 4**. Server-rendered (no SPA/JWT); auth uses PHP sessions.

## Commands

```bash
# Dev
symfony serve                 # dev server (or: php -S localhost:8000 -t public)
npm run watch                 # rebuild assets on change (dev) — encore dev --watch
npm run build                 # production asset build → public/build/
npm run dev-server            # encore dev-server

# Database (Doctrine — schema is migration-driven, never edit DB by hand)
php bin/console make:migration                              # generate migration from entity changes
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate                    # entities <-> schema coherence

# Lint / debug
php bin/console lint:twig templates
php bin/console lint:container
php bin/console debug:router
php bin/console cache:clear [--env=prod]

# Tests (PHPUnit 9 via Symfony bridge)
php bin/phpunit                       # run all tests
php bin/phpunit --filter testName     # single test by method name
php bin/phpunit path/to/SomeTest.php  # single file
```

Custom console commands live in `src/Command/`: `app:create-admin` (CreateAdminCommand), plus a command to attach an order to an account.

## Architecture

Standard Symfony layout under `src/`, PSR-4 `App\` → `src/`. Routing is via **PHP attributes** (`#[Route(...)]`) on controllers, not YAML.

- **`src/Controller/`** — public controllers (`PublicController`, `AvisController`, `DemandeSurMesureController`, `MonDepotController`, `InvitationController`, `MollieWebhookController`).
- **`src/Controller/Admin/`** — one controller per admin resource, each prefixed `#[Route('/admin')]` + `#[IsGranted('ROLE_ADMIN')]`. `OrganisationAdminController` powers the drag-and-drop catalog reordering (categories → collections → articles via the `ordre` field).
- **`src/Controller/Api/`** — JSON endpoints (e.g. `UploadController`) called from admin AJAX.
- **`src/Service/`** — business logic. Key services: `CartService` (session cart), `MollieService` + `MollieWebhookController` (payment), `PrintfulService` (print-on-demand), `AccountProvisioningService` (creates a user account after payment), `MailerService` (transactional emails), `UploadService` (GD image handling), `SlugifyService`.
- **`src/EventSubscriber/`** — `SecurityHeadersSubscriber` (CSP with per-request nonces, hardened headers), `PageViewSubscriber` (analytics `PageView` tracking), `NavbarSubscriber` (injects nav data).
- **`src/Twig/AppExtension.php`** — custom filters/functions: `price`, `date_french`, `truncate`, `to_string`, `csp_nonce`, `encore_entry`.
- **`src/Entity/` & `src/Repository/`** — ~25 entities. Core domain: `Article`, `Variante`, `VarianteTemplate`, `Category`, `ProductCollection`, `Commande`, `User`, `DemandeSurMesure`, `DepotVente`, `PropositionCommerciale`, `CodeReduction`, `GrillePrix`, `Fournisseur`, `BoutiqueRelais`, `SiteConfig`.

### Security model (`config/packages/security.yaml`)
Role hierarchy `ROLE_ADMIN → ROLE_DEPOT_VENTE`. Access control: `/admin` = ROLE_ADMIN, `/mon-depot` = ROLE_DEPOT_VENTE (partner consignment area), `/profil` = ROLE_USER. Form login with CSRF, login throttling (5 attempts / 15 min), remember-me (1 week). Password hashing `auto` (bcrypt). CSRF token id `app` is also enforced on AJAX (header `X-CSRF-Token`); rate-limiter guards login + custom-order requests; honeypots for anti-spam.

### Frontend / assets
Tailwind theme "Émeraude" — tokens centralized in `tailwind.config.js` and `assets/styles/app.css`: `primary #2F7A5B`, `secondary #4FB48A`, `accent #E7F4EE`, `dark #143027`. Fonts are **self-hosted** woff2 in `assets/fonts/` (Bricolage Grotesque titles, Hanken Grotesk body) — no Google CDN, required by the CSP `font-src 'self'`.

Encore outputs hashed assets; templates reference them via the `encore_entry` Twig function reading `entrypoints.json` — never hardcode build hashes. After `npm run build`, **`public/build/` is committed to git** so deployment needs no Node.

## Deployment (Hostinger, git-pull based)

```bash
git checkout main && git pull
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

No Node on the server (assets are pre-built and versioned). Production secrets live in `.env.prod.local` (`DATABASE_URL`, `MAILER_DSN`, Mollie/Printful keys). See `DEPLOY-HOSTINGER.md`. Ensure `var/` (775) and `public/uploads/` (755) permissions.

## Conventions

- Generate DB changes through entities + `make:migration`; do not write raw schema SQL.
- Admin resources follow the one-controller-per-resource pattern under `Controller/Admin/` — mirror it for new resources.
- Keep design tokens in `tailwind.config.js` / `app.css` (synced with the external "Claude Design" `tokens.css`); avoid inline colors.
- Reference docs: `README.md`, `COMMANDES-UTILES.md` (command cheatsheet), `CHANGELOG.md` (+ dated `CHANGELOG-YYYY-MM-DD.md`), `SECURITY-AUDIT-*.md`, `PROFIL_TDBR.md` (brand context).
