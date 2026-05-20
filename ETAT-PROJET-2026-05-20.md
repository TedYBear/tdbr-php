# État du projet TDBR site_v3 — 2026-05-20

Stack : Symfony 6.3, PHP 8.1, MySQL 8 (Doctrine ORM), Twig, Tailwind CSS 3.4, Alpine.js 3.13, Webpack Encore

---

## Controllers

### Public

**PublicController** (~1600 lignes) — cœur du site public
- Accueil, catalogue, catégorie, collection, fiche article
- Panier (session) : add, remove, update
- Checkout : Mollie (carte) + virement
- Propositions commerciales : vue client, paiement, virement
- Auth : connexion, inscription, profil (commandes, codes promo, changement mot de passe)
- Pages info : workflow, partenaires, livraison, tarifs, CGV, mentions légales, contact
- Entités : Article, ProductCollection, Category, Variante, GrillePrix, ArticleImage, Commande, CodeReduction, PropositionCommerciale, DemandeSurMesure, User, Message, SiteConfig, BoutiqueRelais, Fournisseur
- Services : CartService, MollieService, MailerService, PrintfulService

**InvitationController** — activation compte par lien (token unique + expiration)

**MonDepotController** [ROLE_DEPOT_VENTE] (~340 lignes)
- `/mon-depot` : tableau de bord stock + transactions
- Ajout/retrait articles du suivi
- Enregistrement ventes locales
- Fond de caisse (ajout/retrait)
- Entités : DepotVente, DepotVenteStockItem, DepotVenteTransaction

**MollieWebhookController** — webhook Mollie (idempotent, payee ← en_attente)

**AvisController** — soumission avis clients

**DemandeSurMesureController** — formulaire demande sur mesure

### Admin (`/admin` → ROLE_ADMIN)

| Controller | Rôle |
|---|---|
| DashboardController | Stats (articles, catégories, collections, commandes, devis, messages non lus) |
| ArticleAdminController | CRUD articles, images, variantes, grille tarifaire, lien Printful |
| CommandeAdminController | Liste + filtre statut, détail, changement statut, validation virement |
| PropositionCommercialeAdminController | Créer/éditer devis, envoyer par email (token unique) |
| DepotVenteAdminController | CRUD dépôts, attribution ROLE_DEPOT_VENTE, stock, transactions |
| CategoryAdminController | CRUD catégories |
| CollectionAdminController | CRUD collections |
| GrillePrixAdminController | CRUD grilles tarifaires (paliers quantité) |
| CodeReductionAdminController | Codes promo, montants, dates, assignation utilisateur |
| AvisAdminController | Modération avis clients |
| MessageAdminController | Messages contact, marquage lu/non lu |
| UserAdminController | CRUD comptes, attribution rôles, invitation par lien |
| PrintfulAdminController | Import produits/variantes depuis Printful, sync IDs |
| FournisseurAdminController | CRUD fournisseurs (Vistaprint, Printful…) |
| DemandeSurMesureAdminController | Suivi devis custom, création PropositionCommerciale associée |
| MarketingAdminController | Génération affiche PDF avec QR code, visuels thématiques |
| SiteConfigAdminController | Désactiver paiements, campagne codes cadeaux, frais livraison Vistaprint |
| BoutiqueRelaisAdminController | Points relais de livraison |
| UploadAdminController | Gestion uploads images |
| TemplateAdminController | Templates de variantes réutilisables |
| CaracteristiqueAdminController | Définition options (Taille: S M L XL, etc.) |

---

## Entités Doctrine (23)

### Principales

**Article**
- `nom`, `slug`, `description`, `prixBase`, `actif`, `enVedette`, `personnalisable`, `ordre`
- `printfulProductId` (bigint, sync Printful)
- Relations : `collection` (ManyToOne), `fournisseur` (ManyToOne), `grillePrix` (ManyToOne), `varianteTemplate` (ManyToOne), `images` (OneToMany → ArticleImage), `variantes` (OneToMany → Variante)

**User**
- `email`, `password` (bcrypt), `roles` (array), `prenom`, `nom`, `telephone`
- `inviteToken`, `inviteTokenExpiresAt` (invitation par lien)
- Rôles : ROLE_USER, ROLE_DEPOT_VENTE, ROLE_ADMIN (hiérarchie : ROLE_ADMIN hérite ROLE_DEPOT_VENTE)

**Commande**
- `numero` (CMD-xxx ou PROP-xxx), `client` (json), `adresseLivraison` (json), `articles` (json)
- `total`, `modePaiement` (mollie/virement/admin), `statut`, `notes`
- `molliePaymentId`, `printfulOrderId`, `modeLivraison` (json : type, label, prix, point relais)
- `reduction`, `factureSentAt`, `user` (ManyToOne nullable)
- Flux statuts : `en_attente` → `payee` / `en_attente_virement` → [admin valide] → `payee`

**PropositionCommerciale**
- Tarification : `coutDesign` + `prixPublic` + `fraisManutention` - `ristourne` = `prixTotal`
- `clientEmail`, `clientNom`, `messagePersonnel` (email uniquement, pas dans PDF)
- `statut` : brouillon → envoyee → acceptee → en_attente_virement → payee
- `token` (64 chars, unique), `user` (ManyToOne), `demandeSurMesure` (ManyToOne), `commande` (ManyToOne)
- `parent`/`clones` (self-reference, versions)

**GrillePrix**
- `nom`, `description`, `paliers` (json : 4 tranches), `lignes` (json : détail 1-10)
- Palier exemple : `{label, min, max, prixFournisseur, prixVente}`

**CodeReduction**
- `code`, `montant`, `statut` (actif/utilisé/expiré), `dateDebut`, `dateExpiration`
- `user` (ManyToOne, null = global), `commande` (ManyToOne), `isCampaignGift`, `recipientEmail`

**DemandeSurMesure**
- `nom`, `email`, `telephone`, `concept`, `contexte`, `supports` (json), `quantite`, `moyenContact`
- `statut` : nouveau → en_cours → proposition_envoyee

**DepotVente**
- `nom`, `adresse`, `codePostal`, `ville`, `telephone`, `email`, `actif`, `fondDeCaisse`
- `user` (ManyToOne), `stockItems` (OneToMany), `transactions` (OneToMany)

**DepotVenteTransaction**
- `type` : REASSORT, VENTE, FOND_AJOUT, FOND_RETRAIT
- `montantFond`, `lignes` (OneToMany → DepotVenteTransactionLigne), `note`

**DepotVenteTransactionLigne**
- `variante`, `variante_label`, `quantite`, `prixEstime`, `prixReel`

**DepotVenteStockItem**
- `depotVente`, `variante`, `quantite`

### Secondaires

| Entité | Rôle |
|---|---|
| Category | Catégories produits (nom, slug, image, ordre, actif) |
| ProductCollection | Collections au sein d'une catégorie (ManyToOne → Category) |
| Variante | Variantes article (nom, sku, deltaPrix, valeurs json, printfulVariantId, actif) |
| ArticleImage | Images article (filename, url, ordre) |
| Avis | Avis clients (user, contenu, note, photoFilename, visible, ordre) |
| Message | Messages contact (nom, email, sujet, message, lu) |
| BoutiqueRelais | Points relais livraison (nom, adresse, codePostal, ville, actif) |
| Fournisseur | Fournisseurs (Vistaprint, Printful…) |
| SiteConfig | Singleton config globale (paymentsDisabled, giftActive, giftType, giftValue, giftMaxBeneficiaires, fraisVistaprintDomicile) |
| VarianteTemplate | Templates de variantes réutilisables |
| Caracteristique | Options (Taille, Couleur…) |
| CaracteristiqueValeur | Valeurs options (S, M, L, Rouge…) |

---

## Services (7)

**CartService** — panier en session
- Déduplication par ID + hash choices
- Calcul prix par paliers tarifaires (grille quantité, agrégation inter-articles)
- `addItem`, `removeItem`, `updateQuantity`, `getCart`, `getTotal`, `getTotalQuantity`, `getGrilleTotals`, `clear`

**MollieService** — paiements Mollie
- `createPayment(amount, description, redirectUrl, webhookUrl, metadata)`
- `getPayment(id)` → vérification statut
- Config : `MOLLIE_API_KEY`

**PrintfulService** — impression à la demande
- `createDraftOrder(Commande, items)` — commande brouillon Printful
- `importProducts()`, `syncVariants()` — sync admin
- Config : `PRINTFUL_API_KEY`, `PRINTFUL_STORE_ID`

**MailerService** (~200 lignes)
- `sendRegistrationConfirmation`, `sendOrderConfirmation`, `sendVirementCommande`
- `sendFactureAcquittee` (PDF Dompdf en attachment)
- `sendGiftCode`, `sendInvitation`, `sendPropositionEmail`
- Config : `MAILER_DSN`, `MAILER_FROM`, `MAILER_BCC`

**SlugifyService** — génération slugs URL uniques

**UploadService** — gestion fichiers uploadés (`public/uploads/images/`, `public/uploads/templates/`)

**JWTService** — tokens JWT (présent mais usage optionnel)

---

## Sécurité

```yaml
role_hierarchy:
  ROLE_ADMIN: [ROLE_DEPOT_VENTE]

access_control:
  /admin → ROLE_ADMIN
  /mon-depot → ROLE_DEPOT_VENTE
  /profil → ROLE_USER
```

Auth : form_login (session-based), remember_me 1 semaine, logout → home

---

## Frontend

- **Tailwind CSS 3.4** : palette custom marron/or/ivoire (primary #8B7355, secondary #D4AF7A, accent #F5E6D3, dark #2C2416), fonts Inter + Space Grotesk
- **Alpine.js 3.13** : interactions légères (panier AJAX, checkout, drag & drop images)
- **Webpack Encore** : entry `app.js`, versioning en prod, copie assets images
- **Dompdf** : génération PDF (factures, propositions)
- **Endroid QR Code** : affiches marketing dépôt-vente

---

## Migrations

45 migrations Doctrine. Dernière : `V20260426020000` (cleanup guides_tailles).
Chronologie clé :
- `V20260217+` : schéma initial
- `V20260225+` : DepotVente
- `V20260302+` : PropositionCommerciale
- `V20260310+` : CodeReduction, SiteConfig
- `V20260416+` : Printful sync
- `V20260426+` : VarianteTemplate, cleanup

---

## Fonctionnalités complètes

### Site public
1. Accueil
2. Catalogue / Catégorie / Collection (pagination)
3. Fiche article (variantes, grille tarifaire, panier)
4. Panier (session, codes promo, calcul paliers)
5. Checkout (livraison domicile/relais/Toulouse, Mollie/virement)
6. Propositions commerciales (vue client, paiement)
7. Espace client (commandes, codes promo, mot de passe)
8. Demande sur mesure
9. Pages info, CGV, mentions légales, contact
10. Mon Dépôt [ROLE_DEPOT_VENTE] (stock, ventes, fond caisse)

### Admin
1. Dashboard stats
2. Articles (CRUD + images + variantes + Printful)
3. Commandes (statuts, validation virement)
4. Propositions commerciales (devis, envoi email)
5. Dépôt-vente (CRUD, stock, transactions)
6. Catégories & Collections
7. Grilles tarifaires
8. Codes promo (globaux, personnels, cadeaux)
9. Avis clients
10. Messages contact
11. Utilisateurs (CRUD, rôles, invitation)
12. Import Printful
13. Marketing (affiches PDF + QR code)
14. Configuration globale (paiements, campagne cadeaux, frais livraison)
15. Templates variantes, caractéristiques
16. Fournisseurs, points relais

---

## Intégrations externes

| Service | Usage |
|---|---|
| Mollie | Paiements carte (commandes + propositions) |
| Printful | Import produits, commandes impression à la demande |
| SMTP Gmail | Envoi emails (confirmation, facture, invitation…) |
| Dompdf | PDF factures et propositions |
| Endroid QR Code | Affiches marketing |

---

## Flux métier

```
Commande :
  en_attente → payee (Mollie webhook)
  en_attente → en_attente_virement → payee (validation admin manuelle)

Proposition :
  brouillon → envoyee → acceptee → en_attente_virement/payee

Demande sur mesure :
  nouveau → en_cours → proposition_envoyee → [PropositionCommerciale créée]
```

---

## Derniers commits (au 2026-05-20)

```
aadaf6a Navbar : lien Mon dépôt-vente dans le menu avatar pour ROLE_DEPOT_VENTE
f1e6939 Mon dépôt-vente : page complète identique à la vue admin
1e94cfb Security : ROLE_ADMIN hérite de ROLE_DEPOT_VENTE (role_hierarchy)
0ea8944 Profil : lien vers l'espace dépôt-vente pour ROLE_DEPOT_VENTE
0ff9987 Utilisateurs admin : toggle ROLE_DEPOT_VENTE depuis la liste
6e2bf36 Profil : permet la modification du mot de passe
6e4e84e Fix syntaxe PHP : apostrophe dans addFlash MonDepotController
479e6e8 Ajoute l'espace dépôt-vente (ROLE_DEPOT_VENTE)
53ead14 Affiche TDBR : corrige label Fanarts → Fanart
0a6a795 Affiche TDBR : remplace obsessions par passions
33062f2 Affiche TDBR : mise à jour des visuels thématiques (v2)
e8dc574 Affiche TDBR : visuels samples optimisés
aa45dcb Affiche TDBR : ajoute les 3 visuels thématiques
29cf64f Affiche TDBR : grille de 3 visuels thématiques + intro recentrée
aa71895 Marketing : seconde affiche "découverte TDBR" axée sur les thèmes
```
