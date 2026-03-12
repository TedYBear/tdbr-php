# Changelog site_v3 — Session 2026-03-12

## Résumé des sessions de travail (10-12 mars 2026)

---

## Factures (commits 2dcf9e4 → 1975fbe)

**Fichier :** `templates/commandes/facture.html.twig`

Refonte complète de la mise en page de la facture :
- Logo TDBR affiché (`public/build/images/TDBR.png`, 80px)
- Identité vendeur : Emmanuel Chauveau, 39 Bd Victor Hugo, Rés. Léopoldine B36, 31770 Colomiers
- SIREN 538 044 231 dans l'en-tête et le pied de page
- Tagline corrigée : "Créations de produits personnalisés" (plus "Teddy Bears Artisanaux")
- Suppression de l'email ; remplacement par lien vers le formulaire de contact
- Mention légale TVA : "TVA non applicable – Art. 293 B du CGI"

---

## Génération de facture depuis propositions payées (commit 5d570c3)

**Fichiers :** `src/Controller/PublicController.php`, `src/Controller/Admin/PropositionCommercialeAdminController.php`

- Route publique `GET /proposition/{token}/facture` → facture HTML si statut `payee`
- Route admin `GET /admin/propositions/{id}/facture` → même rendu
- Réutilise le template `commandes/facture.html.twig`

---

## Décomposition du prix en 4 lignes (commit 5efa6fc)

**Fichiers :** `src/Entity/PropositionCommerciale.php`, migration `Version20260312010000.php`, `src/Service/MailerService.php`, `templates/propositions/pdf.html.twig`

Nouvelle structure tarifaire de `PropositionCommerciale` :
1. `coutDesign` (nullable) — création graphique et adaptation aux supports
2. `prixPublic` (requis) — prix de base avec description du contenu
3. `fraisManutention` (nullable) — frais de manutention
4. `ristourne` (nullable) — remise à déduire

`prixTotal = coutDesign + prixPublic + fraisManutention - ristourne`

**PDF joint au mail :**
- `MailerService::sendProposition()` génère un PDF via Dompdf (`dompdf/dompdf ^3.1`)
- `MailerService::generatePropositionPdf()` rend `propositions/pdf.html.twig` et attache le PDF
- Template PDF standalone (pas d'extends), compatible Dompdf
- Logo encodé en base64 passé depuis `MailerService` (chemin `$projectDir/public/build/images/TDBR.png`)
- `config/services.yaml` : binding `$projectDir: '%kernel.project_dir%'` sur `MailerService`

**Lien contact pré-rempli dans l'email :**
- Sujet : "À propos de la proposition commerciale {TOKEN[:12]}"

---

## Autocomplete client + paiement par virement (commit 8f58fee)

**Fichiers :** `src/Controller/Admin/PropositionCommercialeAdminController.php`, `templates/admin/propositions/form.html.twig`, `templates/admin/propositions/index.html.twig`, `templates/public/proposition.html.twig`, `src/Controller/PublicController.php`

**Autocomplete client :**
- Route `GET /admin/propositions/clients/search?q=` → JSON des utilisateurs correspondants
- Alpine.js `clientSearch()` dans le formulaire admin
- Sélection remplit automatiquement les champs email et nom

**Paiement par virement :**
- Nouveau statut `en_attente_virement`
- Route publique `POST /proposition/{token}/virement` — crée la commande avec `modePaiement = 'virement'`
- Page client affiche l'IBAN : `FR76 1695 8000 0122 5925 2066 395` + référence à indiquer
- Route admin `POST /admin/propositions/{id}/marquer-payee` — confirme réception, déclenche mêmes effets que Stripe (statut payee, envoi email confirmation)
- Badge "Virement en attente" (amber) dans index et formulaire admin

---

## Fix calcul automatique du total (commit 6fa835d)

**Fichier :** `templates/admin/propositions/form.html.twig`

- Initialisation Alpine.js `propositionForm()` directement depuis les valeurs Twig (plus de `document.querySelector`)
- Suppression des attributs `value=""` sur les inputs avec `x-model` (conflit résolu)
- Total réactif : `coutDesign + prixPublic + fraisManutention - ristourne`

---

## Fix cafile Composer (commit daedd26)

**Fichier :** `composer.json`

- Suppression du chemin local `C:\Users\Manu\cacert.pem` en dur dans la config Composer
- Remplacement par `"secure-http": false, "disable-tls": true` pour compatibilité serveur Hostinger

---

## Logo PDF, message personnel, gestion index (commit f9f5328)

**Fichiers multiples**

**Logo dans le PDF :**
- `MailerService::generatePropositionPdf()` lit le logo, l'encode en base64, le passe au template
- `templates/propositions/pdf.html.twig` : `<img src="{{ logoBase64 }}">` — compatible Dompdf (pas de remote)
- Fallback texte "TDBR" si le fichier est absent

**Message personnel (email uniquement) :**
- Nouveau champ `messagePersonnel` (nullable text) sur `PropositionCommerciale`
- Migration `Version20260312020000.php` : colonne `message_personnel LONGTEXT DEFAULT NULL`
- Textarea dans le formulaire admin (carte avec bordure gauche colorée)
- Passé à `emails/proposition.html.twig` uniquement — absent du PDF et de la page client
- Si renseigné : remplace le texte d'introduction générique par le message personnalisé

**Page index admin — actions directes :**
- Suppression du lien "Modifier" systématique
- Dropdown de statut (`brouillon / envoyee / acceptee / en_attente_virement / payee`) + bouton OK par ligne
- Bouton Supprimer par ligne (confirmation JS)
- Nouvelle route `POST /admin/propositions/{id}/statut`
- Bouton "Modifier" visible uniquement pour statut `brouillon`

---

## Import maquettes Printful pour articles existants (commit 16b9482)

**Fichier :** `src/Controller/Admin/PrintfulAdminController.php`

- Correction : les maquettes n'étaient jamais importées pour les articles déjà en base
- Import différentiel : compare les URLs existantes, n'importe que les nouvelles
- Ajout incrémental avec ordre préservé, pas de doublons

---

## Drag & drop réordonnancement des maquettes (commit 432e3a4)

**Fichier :** `templates/admin/articles/form.html.twig`

- Drag & drop natif HTML5 via Alpine.js (sans dépendance externe)
- `dragIndex`, `dragStart()`, `dragOver()`, `dragEnd()` dans le composant Alpine
- Opacité 35% pendant le glissement, icône de poignée visible
- Clé `x-for` stable (URL plutôt qu'index) pour éviter les bugs de rendu
- Message d'aide si plus d'une image présente
- L'ordre soumis via hidden inputs `images[]` est persisté en base (`ordre`)

---

## Réactivation du paiement Stripe (commit bf3dd48)

**Fichier :** `templates/public/checkout.html.twig`

- Remplacement du message "indisponible" par `<div id="payment-element">` (Stripe Elements)
- Ajout `<div id="payment-message">` pour les erreurs Stripe
- Bouton de paiement restauré : `@click="pay()"`, montant dynamique via Alpine, spinner de chargement
- Backend inchangé (StripeService, webhook, PaymentIntent étaient déjà fonctionnels)

---

## État infrastructure

- **Hébergement :** Hostinger (SSH disponible)
- **Composer :** `disable-tls: true` nécessaire (certificats SSL serveur invalides)
- **Dompdf :** `dompdf/dompdf ^3.1` installé — génération PDF côté serveur
- **Déploiement :** `git pull && php bin/console doctrine:migrations:migrate --no-interaction && php bin/console cache:clear`
