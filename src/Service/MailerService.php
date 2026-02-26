<?php

namespace App\Service;

use App\Entity\Commande;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'noreply@tdbr.fr',
        private string $fromName = 'TDBR'
    ) {
    }

    /**
     * Envoie un email de confirmation d'inscription
     */
    public function sendRegistrationConfirmation(string $toEmail, string $userName): void
    {
        $html = $this->twig->render('emails/registration.html.twig', [
            'userName' => $userName
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Bienvenue sur TDBR !')
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de confirmation de commande
     */
    public function sendOrderConfirmation(Commande $commande, string $confirmationUrl = ''): void
    {
        $html = $this->twig->render('emails/order_confirmation.html.twig', [
            'commande'        => $commande,
            'confirmationUrl' => $confirmationUrl,
        ]);

        $client = $commande->getClient();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($client['email'])
            ->subject('Confirmation de commande ' . $commande->getNumero())
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie une notification de changement de statut de commande
     */
    public function sendOrderStatusUpdate(array $commande, string $newStatus): void
    {
        $statusLabels = [
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'en_cours' => 'En cours de préparation',
            'expediee' => 'Expédiée',
            'livree' => 'Livrée',
            'annulee' => 'Annulée'
        ];

        $html = $this->twig->render('emails/order_status.html.twig', [
            'commande' => $commande,
            'newStatus' => $newStatus,
            'statusLabel' => $statusLabels[$newStatus] ?? $newStatus
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($commande['client']['email'])
            ->subject('Mise à jour de votre commande ' . $commande['numero'])
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie une notification de nouveau message contact à l'admin
     */
    public function sendContactNotification(array $message): void
    {
        $html = $this->twig->render('emails/contact_notification.html.twig', [
            'message' => $message
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->fromEmail) // Envoyer à l'admin
            ->replyTo($message['email'])
            ->subject('Nouveau message de contact - ' . $message['sujet'])
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie une réponse à un message de contact
     */
    public function sendContactReply(string $toEmail, string $subject, string $messageContent): void
    {
        $html = $this->twig->render('emails/contact_reply.html.twig', [
            'message' => $messageContent
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Re: ' . $subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
