<?php
namespace App\Controller;

use App\Entity\DemandeSurMesure;
use App\Form\DemandeSurMesureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class DemandeSurMesureController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/devis', name: 'devis', methods: ['GET', 'POST'])]
    public function devis(Request $request, MailerInterface $mailer): Response
    {
        // Si non connecté, bloquer la soumission et rediriger vers login
        if (!$this->getUser() && $request->isMethod('POST')) {
            $this->addFlash('error', 'Vous devez être connecté pour envoyer une demande sur-mesure.');
            return $this->redirectToRoute('connexion', ['redirect' => '/devis']);
        }

        $form = $this->createForm(DemandeSurMesureType::class);

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

            $demande = new DemandeSurMesure();
            $demande->setNom($data['nom']);
            $demande->setEmail($data['email']);
            $demande->setTelephone($data['telephone'] ?? null);
            $demande->setConcept($data['concept']);
            $demande->setContexte($data['contexte'] ?? null);
            $demande->setSupports($supports);
            $demande->setAutreSupport($request->request->get('autreSupport') ?: null);
            $demande->setQuantite($data['quantite']);
            $demande->setMoyenContact($data['moyenContact']);
            $demande->setMessageAdditionnel($data['messageAdditionnel'] ?? null);

            $this->em->persist($demande);
            $this->em->flush();

            // Notification email
            try {
                $from = $_ENV['MAILER_FROM'] ?? 'tdbrlaboutique@gmail.com';
                $corps = "Nouvelle demande sur-mesure\n\n"
                    . "Nom : " . $demande->getNom() . "\n"
                    . "Email : " . $demande->getEmail() . "\n"
                    . ($demande->getTelephone() ? "Téléphone : " . $demande->getTelephone() . "\n" : "")
                    . "\nProjet :\n" . $demande->getConcept() . "\n"
                    . ($demande->getContexte() ? "\nContexte : " . $demande->getContexte() . "\n" : "")
                    . "\nSupports souhaités : " . implode(', ', $demande->getSupports()) . "\n"
                    . "Quantité : " . $demande->getQuantite() . "\n"
                    . "Contact préféré : " . $demande->getMoyenContact() . "\n"
                    . ($demande->getMessageAdditionnel() ? "\nMessage : " . $demande->getMessageAdditionnel() . "\n" : "");

                $email = (new Email())
                    ->from($from)
                    ->to($from)
                    ->replyTo($demande->getEmail())
                    ->subject('[TDBR Sur-mesure] Nouvelle demande de ' . $demande->getNom())
                    ->text($corps);
                $mailer->send($email);
            } catch (\Throwable $e) {
                file_put_contents(
                    __DIR__ . '/../../var/log/mail_error.log',
                    date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }

            $this->addFlash('success', 'Votre demande sur-mesure a été envoyée ! Nous vous contacterons dans les plus brefs délais.');
            return $this->redirectToRoute('devis');
        }

        return $this->render('public/devis.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
