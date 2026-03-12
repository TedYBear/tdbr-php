<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\PropositionCommerciale;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private string $projectDir,
        private string $fromEmail = 'noreply@tdbr.fr',
        private string $fromName = 'TDBR'
    ) {
    }

    /**
     * Envoie un email de confirmation d'inscription
     */
    public function sendRegistrationConfirmation(string $toEmail, string $userName): void
    {
        $catalogueUrl = $this->urlGenerator->generate('catalogue', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $html = $this->twig->render('emails/registration.html.twig', [
            'userName'     => $userName,
            'catalogueUrl' => $catalogueUrl,
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
     * Envoie les instructions de virement pour une commande classique
     */
    public function sendVirementCommande(Commande $commande): void
    {
        $client = $commande->getClient();

        $html = $this->twig->render('emails/virement_commande.html.twig', [
            'commande' => $commande,
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($client['email'])
            ->subject('Instructions de virement — Commande ' . $commande->getNumero())
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
     * Envoie un code cadeau suite à une commande (campagne)
     */
    public function sendGiftCode(string $toEmail, string $code, float $montant, string $type): void
    {
        $html = $this->twig->render('emails/gift_code.html.twig', [
            'code'    => $code,
            'montant' => $montant,
            'type'    => $type,
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('Un cadeau vous attend chez TDBR !')
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie une proposition commerciale au client avec le PDF en pièce jointe
     */
    public function sendProposition(PropositionCommerciale $proposition): void
    {
        $publicUrl = $this->urlGenerator->generate(
            'proposition_view',
            ['token' => $proposition->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $contactUrl = $this->urlGenerator->generate(
            'contact',
            ['sujet' => 'À propos de la proposition commerciale ' . strtoupper(substr($proposition->getToken(), 0, 12))],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $html = $this->twig->render('emails/proposition.html.twig', [
            'proposition'      => $proposition,
            'publicUrl'        => $publicUrl,
            'contactUrl'       => $contactUrl,
            'messagePersonnel' => $proposition->getMessagePersonnel(),
        ]);

        $subject = 'Votre proposition commerciale TDBR';
        if ($proposition->getClientNom()) {
            $subject .= ' — ' . $proposition->getClientNom();
        }

        $pdfContent = $this->generatePropositionPdf($proposition);
        $pdfFilename = 'proposition-tdbr-' . substr($proposition->getToken(), 0, 12) . '.pdf';

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($proposition->getClientEmail())
            ->subject($subject)
            ->html($html)
            ->attach($pdfContent, $pdfFilename, 'application/pdf');

        $this->mailer->send($email);
    }

    private function generatePropositionPdf(PropositionCommerciale $proposition): string
    {
        $logoPath = $this->projectDir . '/public/build/images/TDBR.png';
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $html = $this->twig->render('propositions/pdf.html.twig', [
            'proposition' => $proposition,
            'logoBase64'  => $logoBase64,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
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
