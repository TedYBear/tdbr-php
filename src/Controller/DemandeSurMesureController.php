<?php
namespace App\Controller;

use App\Entity\DemandeSurMesure;
use App\Entity\User;
use App\Form\DemandeSurMesureType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

class DemandeSurMesureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private RateLimiterFactory $demandeSurMesureLimiter,
    ) {
    }

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
            // Honeypot anti-spam : champ caché qui doit rester vide
            if ($request->request->get('website')) {
                $this->addFlash('success', 'Votre demande sur-mesure a été envoyée ! Nous vous contacterons dans les plus brefs délais.');
                return $this->redirectToRoute('devis');
            }

            // Limitation du débit (anti-spam)
            if (!$this->demandeSurMesureLimiter->create($request->getClientIp() ?? 'anon')->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de demandes envoyées. Merci de réessayer dans quelques minutes.');
                return $this->redirectToRoute('devis');
            }

            $data = $form->getData();

            // Récupérer les supports depuis la requête (checkboxes)
            $supportsRaw = $request->request->all('supports') ?: [];
            $supports = array_values(array_filter($supportsRaw));

            $demande = new DemandeSurMesure();
            $demande->setSource('devis');
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

            // Rattachement au compte + complétion des infos manquantes du compte (sans écraser l'existant)
            $user = $this->getUser();
            if ($user instanceof User) {
                $demande->setUser($user);

                if (!$user->getTelephone() && !empty($data['telephone'])) {
                    $user->setTelephone($data['telephone']);
                }
                if (!$user->getPrenom() && !$user->getNom() && !empty($data['nom'])) {
                    $user->setNom($data['nom']);
                }
            }

            $this->em->persist($demande);
            $this->em->flush();

            $this->notifierAtelier($mailer, $demande);

            $this->addFlash('success', 'Votre demande sur-mesure a été envoyée ! Nous vous contacterons dans les plus brefs délais.');
            return $this->redirectToRoute('devis');
        }

        return $this->render('public/devis.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Demande de personnalisation soumise depuis la modale d'une fiche produit (AJAX JSON).
     * Ouverte aux invités ; rattachée au compte si l'utilisateur est connecté.
     */
    #[Route('/demande-personnalisation', name: 'demande_personnalisation', methods: ['POST'])]
    public function personnalisation(Request $request, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfValid($request)) {
            return $this->json(['error' => 'Token de sécurité invalide, veuillez recharger la page'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        // Honeypot : champ qui doit rester vide. Rempli => bot. On simule un succès pour ne pas l'informer.
        if (!empty($data['website'])) {
            return $this->json(['success' => true]);
        }

        // Limitation du débit (anti-spam)
        if (!$this->demandeSurMesureLimiter->create($request->getClientIp() ?? 'anon')->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes. Merci de réessayer dans quelques minutes.'], 429);
        }

        // Réservé aux utilisateurs connectés
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Vous devez être connecté pour envoyer une demande de personnalisation.'], 401);
        }

        // Produit
        $articleId = isset($data['articleId']) ? (int) $data['articleId'] : 0;
        $article = $articleId > 0 ? $this->articleRepo->find($articleId) : null;
        if (!$article || !$article->isActif()) {
            return $this->json(['error' => 'Produit introuvable'], 404);
        }

        // Type de demande (liste fermée)
        $typesValides = [
            'Couleur différente',
            'Taille non répertoriée',
            'Modèle de T-shirt - H/F/Enfant',
        ];
        $typeDemande = (string) ($data['typeDemande'] ?? '');
        if (!in_array($typeDemande, $typesValides, true)) {
            return $this->json(['error' => 'Veuillez choisir un type de demande.'], 400);
        }

        $commentaire = mb_substr(trim((string) ($data['commentaire'] ?? '')), 0, 2000) ?: null;

        // Description : nom de l'article + ID, puis type de demande et commentaire
        $description = 'Article : ' . $article->getNom() . ' (#' . $article->getId() . ')' . "\n"
            . 'Type de demande : ' . $typeDemande . "\n"
            . 'Commentaire : ' . ($commentaire ?? '—');

        // Coordonnées autoritaires depuis le compte
        $demande = new DemandeSurMesure();
        $demande->setSource('fiche_produit');
        $demande->setUser($user);
        $demande->setArticle($article);
        $demande->setNom($user->getFullName() ?: (string) $user->getEmail());
        $demande->setEmail((string) $user->getEmail());
        $demande->setTelephone($user->getTelephone());
        $demande->setConcept($description);
        $demande->setPersonnalisation(['type' => $typeDemande]);
        $demande->setQuantite('1');
        $demande->setMoyenContact('email');
        $demande->setMessageAdditionnel($commentaire);

        $this->em->persist($demande);
        $this->em->flush();

        $this->notifierAtelier($mailer, $demande);

        return $this->json(['success' => true]);
    }

    /**
     * Valide le token CSRF 'app' des appels fetch JSON (header X-CSRF-Token) ou des formulaires (_token).
     */
    private function isCsrfValid(Request $request): bool
    {
        $token = (string) ($request->request->get('_token') ?? $request->headers->get('X-CSRF-Token', ''));

        return $this->isCsrfTokenValid('app', $token);
    }

    /**
     * Envoie l'email de notification à l'atelier. Les erreurs sont journalisées sans bloquer la demande.
     */
    private function notifierAtelier(MailerInterface $mailer, DemandeSurMesure $demande): void
    {
        try {
            $from = $_ENV['MAILER_FROM'] ?? 'tdbrlaboutique@gmail.com';

            $corps = "Nouvelle demande " . ($demande->getSource() === 'fiche_produit' ? 'de personnalisation produit' : 'sur-mesure') . "\n\n"
                . "Nom : " . $demande->getNom() . "\n"
                . "Email : " . $demande->getEmail() . "\n"
                . ($demande->getTelephone() ? "Téléphone : " . $demande->getTelephone() . "\n" : "")
                . ($demande->getUser() ? "Compte : #" . $demande->getUser()->getId() . "\n" : "Compte : invité\n");

            if ($demande->getArticle()) {
                $corps .= "\nProduit : " . $demande->getArticle()->getNom() . " (#" . $demande->getArticle()->getId() . ")\n";
            }

            if ($p = $demande->getPersonnalisation()) {
                if (!empty($p['type'])) {
                    $corps .= "\nType de demande : " . $p['type'] . "\n";
                } else {
                    // Ancien format structuré (modèle/couleur/taille)
                    $corps .= "\nPersonnalisation :\n"
                        . "  Modèle : " . ($p['modele'] ?? '-') . "\n"
                        . "  Couleur : " . ($p['couleur'] ?? '-') . "\n"
                        . "  Taille : " . ($p['taille'] ?? '-') . "\n"
                        . ($p['autre'] ?? null ? "  Autre : " . $p['autre'] . "\n" : "");
                }
            }

            if ($demande->getConcept()) {
                $corps .= "\nProjet :\n" . $demande->getConcept() . "\n";
            }
            if ($demande->getContexte()) {
                $corps .= "\nContexte : " . $demande->getContexte() . "\n";
            }
            if ($demande->getSupports()) {
                $corps .= "Supports souhaités : " . implode(', ', $demande->getSupports()) . "\n";
            }

            $corps .= "Quantité : " . $demande->getQuantite() . "\n"
                . "Contact préféré : " . $demande->getMoyenContact() . "\n"
                . ($demande->getMessageAdditionnel() ? "\nMessage : " . $demande->getMessageAdditionnel() . "\n" : "");

            $sujet = $demande->getSource() === 'fiche_produit'
                ? '[TDBR Personnalisation] Nouvelle demande de ' . $demande->getNom()
                : '[TDBR Sur-mesure] Nouvelle demande de ' . $demande->getNom();

            $email = (new Email())
                ->from($from)
                ->to($from)
                ->replyTo($demande->getEmail())
                ->subject($sujet)
                ->text($corps);
            $mailer->send($email);
        } catch (\Throwable $e) {
            file_put_contents(
                __DIR__ . '/../../var/log/mail_error.log',
                date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }
}
