<?php
namespace App\Controller;

use App\Entity\Devis;
use App\Form\DevisType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class DevisController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/devis', name: 'devis', methods: ['GET', 'POST'])]
    public function devis(Request $request, MailerInterface $mailer): Response
    {
        // Si non connecté, bloquer la soumission et rediriger vers login
        if (!$this->getUser() && $request->isMethod('POST')) {
            $this->addFlash('error', 'Vous devez être connecté pour envoyer une demande de devis.');
            return $this->redirectToRoute('connexion', ['redirect' => '/devis']);
        }

        $form = $this->createForm(DevisType::class);

        // Pré-remplir nom, email et téléphone depuis le compte connecté
        if ($user = $this->getUser()) {
            $form->get('nom')->setData(trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '')));
            $form->get('email')->setData($user->getEmail());
            if ($user->getTelephone()) {
                $form->get('telephone')->setData($user->getTelephone());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Récupérer les supports depuis la requête (checkboxes)
            $supportsRaw = $request->request->all('supports') ?: [];
            $supports = array_values(array_filter($supportsRaw));

            $devis = new Devis();
            $devis->setNom($data['nom']);
            $devis->setEmail($data['email']);
            $devis->setTelephone($data['telephone'] ?? null);
            $devis->setConcept($data['concept']);
            $devis->setContexte($data['contexte'] ?? null);
            $devis->setSupports($supports);
            $devis->setAutreSupport($request->request->get('autreSupport') ?: null);
            $devis->setQuantite($data['quantite']);
            $devis->setMoyenContact($data['moyenContact']);
            $devis->setMessageAdditionnel($data['messageAdditionnel'] ?? null);

            $this->em->persist($devis);
            $this->em->flush();

            // Notification email
            try {
                $from = $_ENV['MAILER_FROM'] ?? 'tdbrlaboutique@gmail.com';
                $corps = "Nouvelle demande de devis\n\n"
                    . "Nom : " . $devis->getNom() . "\n"
                    . "Email : " . $devis->getEmail() . "\n"
                    . ($devis->getTelephone() ? "Téléphone : " . $devis->getTelephone() . "\n" : "")
                    . "\nProjet :\n" . $devis->getConcept() . "\n"
                    . ($devis->getContexte() ? "\nContexte : " . $devis->getContexte() . "\n" : "")
                    . "\nSupports souhaités : " . implode(', ', $devis->getSupports()) . "\n"
                    . "Quantité : " . $devis->getQuantite() . "\n"
                    . "Contact préféré : " . $devis->getMoyenContact() . "\n"
                    . ($devis->getMessageAdditionnel() ? "\nMessage : " . $devis->getMessageAdditionnel() . "\n" : "");

                $email = (new Email())
                    ->from($from)
                    ->to($from)
                    ->replyTo($devis->getEmail())
                    ->subject('[TDBR Devis] Nouvelle demande de ' . $devis->getNom())
                    ->text($corps);
                $mailer->send($email);
            } catch (\Throwable $e) {
                file_put_contents(
                    __DIR__ . '/../../var/log/mail_error.log',
                    date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }

            $this->addFlash('success', 'Votre demande de devis a été envoyée ! Nous vous contacterons dans les plus brefs délais.');
            return $this->redirectToRoute('devis');
        }

        return $this->render('public/devis.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
