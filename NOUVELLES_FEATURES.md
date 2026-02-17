# Nouvelles Fonctionnalités Ajoutées

## 📤 Upload d'Images

### Service UploadService

**Fichier :** `src/Service/UploadService.php`

**Fonctionnalités :**
- Upload de fichiers avec génération de noms uniques
- Validation type MIME (JPG, PNG, GIF, WebP)
- Validation taille (max 5 MB)
- Redimensionnement automatique (max 1200x1200)
- Préservation de la transparence (PNG, GIF)
- Upload multiple
- Suppression de fichiers

**Configuration :** `config/services.yaml`
```yaml
App\Service\UploadService:
    arguments:
        $uploadsDirectory: '%kernel.project_dir%/public/uploads'
```

### Controller Upload Admin

**Fichier :** `src/Controller/Admin/UploadAdminController.php`

**Routes :**
- `POST /admin/upload/image` - Upload une image
- `POST /admin/upload/images` - Upload plusieurs images
- `POST /admin/upload/delete` - Supprime une image

**Usage (AJAX) :**
```javascript
// Upload unique
const formData = new FormData();
formData.append('file', fileInput.files[0]);

const response = await fetch('/admin/upload/image', {
    method: 'POST',
    body: formData
});

const result = await response.json();
// result.path = "/uploads/articles/image-abc123.jpg"
// result.url = "http://localhost:8000/uploads/articles/image-abc123.jpg"

// Upload multiple
const formData = new FormData();
for (let file of fileInput.files) {
    formData.append('files[]', file);
}

const response = await fetch('/admin/upload/images', {
    method: 'POST',
    body: formData
});

const result = await response.json();
// result.uploaded = [{path: "...", url: "..."}, ...]
// result.errors = ["Fichier 0: Type non autorisé", ...]
```

**Validations :**
- Types autorisés : `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- Taille max : 5 MB
- Redimensionnement automatique à 1200x1200 max

---

## 📧 Notifications Email

### Service MailerService

**Fichier :** `src/Service/MailerService.php`

**Méthodes :**

1. **sendRegistrationConfirmation($toEmail, $userName)**
   - Envoyé lors de l'inscription
   - Template : `templates/emails/registration.html.twig`

2. **sendOrderConfirmation($commande)**
   - Envoyé après validation de commande
   - Template : `templates/emails/order_confirmation.html.twig`
   - Contient : récapitulatif articles, total, adresse livraison

3. **sendOrderStatusUpdate($commande, $newStatus)**
   - Envoyé lors du changement de statut
   - Template : `templates/emails/order_status.html.twig`
   - Statuts : en_attente, validee, en_cours, expediee, livree, annulee

4. **sendContactNotification($message)**
   - Envoyé à l'admin lors d'un nouveau message contact
   - Template : `templates/emails/contact_notification.html.twig`

5. **sendContactReply($toEmail, $subject, $messageContent)**
   - Réponse manuelle à un message contact
   - Template : `templates/emails/contact_reply.html.twig`

### Templates Email

**Base :** `templates/emails/base.html.twig`
- Design responsive
- Gradient TDBR (primary → secondary)
- Header, content, footer

**Templates disponibles :**
1. `registration.html.twig` - Bienvenue + lien catalogue
2. `order_confirmation.html.twig` - Récapitulatif complet commande
3. `order_status.html.twig` - Mise à jour statut avec icônes
4. `contact_notification.html.twig` - Notification admin
5. `contact_reply.html.twig` - Réponse à message

### Configuration Mailer

**Fichier :** `config/packages/mailer.yaml`

Pour utiliser en développement (fichiers .eml) :
```yaml
framework:
    mailer:
        dsn: 'null://null'
```

Pour utiliser SMTP en production :
```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

**Variables d'environnement (.env.local) :**
```env
# Gmail
MAILER_DSN=gmail://username:password@default

# SMTP générique
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# Mailtrap (testing)
MAILER_DSN=smtp://user:pass@smtp.mailtrap.io:2525
```

### Usage dans les Controllers

```php
use App\Service\MailerService;

class PublicController extends AbstractController
{
    public function __construct(
        private MailerService $mailerService
    ) {}

    public function inscription(Request $request): Response
    {
        // ... création utilisateur ...

        // Envoyer email de bienvenue
        $this->mailerService->sendRegistrationConfirmation(
            $data['email'],
            $data['prenom']
        );

        // ...
    }

    public function checkout(Request $request): Response
    {
        // ... création commande ...

        // Envoyer confirmation
        $this->mailerService->sendOrderConfirmation((array)$commande);

        // ...
    }
}
```

```php
// Dans CommandeAdminController
public function updateStatus(string $id, Request $request): Response
{
    $statut = $request->request->get('statut');

    // ... mise à jour statut ...

    // Notification client
    $this->mailerService->sendOrderStatusUpdate((array)$commande, $statut);

    // ...
}
```

---

## 🎨 Personnalisation

### Emails

Modifier les templates dans `templates/emails/` :
- Changer les couleurs dans `base.html.twig`
- Personnaliser les messages
- Ajouter votre logo

### Upload

Modifier les paramètres dans `UploadService.php` :
- Taille max : `$maxSize = 5 * 1024 * 1024;`
- Types autorisés : `$allowedMimeTypes = [...]`
- Dimensions max : `resize($path, 1200, 1200)`

---

## ✅ Tests

### Upload d'Images

1. Aller dans l'admin : http://localhost:8000/admin/articles/new
2. Formulaire doit permettre l'upload (à intégrer dans le form)
3. Test via Postman/curl :

```bash
curl -X POST http://localhost:8000/admin/upload/image \
  -H "Cookie: PHPSESSID=..." \
  -F "file=@/path/to/image.jpg"
```

### Emails

En développement (null mailer), les emails sont sauvegardés dans `var/spool/` au format .eml

Pour voir les emails en temps réel, utiliser Mailtrap ou MailHog :

```bash
# Avec Docker + MailHog
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog

# Puis dans .env.local
MAILER_DSN=smtp://localhost:1025
```

Interface web MailHog : http://localhost:8025

---

## 📋 Checklist Intégration

### Upload dans Admin
- [ ] Ajouter input file dans `admin/articles/form.html.twig`
- [ ] JavaScript pour upload AJAX avec preview
- [ ] Afficher les images uploadées
- [ ] Bouton supprimer image

### Emails dans l'Application
- [ ] Activer email inscription dans `PublicController::inscription()`
- [ ] Activer email commande dans `PublicController::checkout()`
- [ ] Activer email statut dans `CommandeAdminController::updateStatus()`
- [ ] Activer notification admin dans `PublicController::contact()`

### Configuration Production
- [ ] Configurer MAILER_DSN dans .env.local
- [ ] Tester envoi email réel
- [ ] Vérifier dossier uploads/ writable
- [ ] Configurer permissions 755 sur public/uploads/

---

## 🔐 Sécurité

**Upload :**
- Validation stricte des types MIME
- Génération de noms de fichiers uniques
- Stockage dans public/uploads/ (en dehors de src/)
- Vérification taille fichier
- Protection CSRF sur les routes admin

**Email :**
- Pas d'injection HTML (Twig auto-escape)
- Validation emails avec Symfony Validator
- Rate limiting recommandé pour éviter spam

---

## 📚 Documentation Symfony

- [Mailer Component](https://symfony.com/doc/current/mailer.html)
- [File Upload](https://symfony.com/doc/current/controller/upload_file.html)
- [Twig Templates](https://twig.symfony.com/doc/3.x/)
