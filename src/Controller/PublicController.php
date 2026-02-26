<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\BoutiqueRelaisRepository;
use App\Repository\CodeReductionRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProductCollectionRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class PublicController extends AbstractController
{
    private const LIVRAISON_OPTIONS = [
        'domicile' => [
            'label'       => 'Livraison à domicile',
            'description' => 'Livraison directe chez vous. Délai estimé : environ 1 semaine. Les colis provenant directement des fournisseurs, plusieurs livraisons peuvent arriver séparément.',
            'prix'        => 0.00,
        ],
        'relais' => [
            'label'       => 'Point relais partenaire',
            'description' => 'Récupérez votre commande chez l\'un de nos partenaires.',
            'prix'        => 0.00,
        ],
        'toulouse' => [
            'label'       => 'Récupération en région Toulousaine',
            'description' => 'Remise en main propre 1 fois par mois. Nous vous contacterons pour convenir des modalités.',
            'prix'        => 0.00,
        ],
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private CategoryRepository $categoryRepo,
        private ProductCollectionRepository $collectionRepo,
        private CommandeRepository $commandeRepo,
        private UserRepository $userRepo,
        private CartService $cartService,
        private StripeService $stripeService,
        private CodeReductionRepository $codeReductionRepo,
        private BoutiqueRelaisRepository $boutiqueRelaisRepo,
    ) {
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('public/home.html.twig');
    }

    #[Route('/presentation', name: 'presentation')]
    public function presentation(): Response
    {
        return $this->render('public/presentation.html.twig');
    }

    #[Route('/presentation/ma-facon-de-travailler', name: 'presentation_workflow')]
    public function presentationWorkflow(): Response
    {
        return $this->render('public/presentation_workflow.html.twig');
    }

    #[Route('/presentation/partenaires', name: 'presentation_partenaires')]
    public function presentationPartenaires(): Response
    {
        return $this->render('public/presentation_partenaires.html.twig');
    }

    #[Route('/presentation/livraison', name: 'presentation_livraison')]
    public function presentationLivraison(): Response
    {
        return $this->render('public/presentation_livraison.html.twig');
    }

    #[Route('/cgv', name: 'cgv')]
    public function cgv(): Response
    {
        return $this->render('public/cgv.html.twig');
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('public/mentions_legales.html.twig');
    }

    #[Route('/catalogue', name: 'catalogue')]
    public function catalogue(Request $request): Response
    {
        $articles = $this->articleRepo->findBy(
            ['actif' => true, 'enVedette' => true],
            ['createdAt' => 'DESC'],
            8
        );

        $categories = $this->categoryRepo->findBy(['actif' => true], ['ordre' => 'ASC']);

        foreach ($categories as $category) {
            $collections = $this->collectionRepo->findBy(['actif' => true, 'categorie' => $category]);
            $count = 0;
            foreach ($collections as $col) {
                $count += $this->articleRepo->count(['actif' => true, 'collection' => $col]);
            }
            $category->articleCount = $count;
        }

        return $this->render('public/catalogue.html.twig', [
            'articles' => $articles,
            'categories' => $categories
        ]);
    }

    #[Route('/categorie/{slug}', name: 'categorie')]
    public function categorie(string $slug): Response
    {
        $category = $this->categoryRepo->findOneBy(['slug' => $slug, 'actif' => true]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable');
        }

        $collections = $this->collectionRepo->findBy(
            ['actif' => true, 'categorie' => $category],
            ['ordre' => 'ASC']
        );

        foreach ($collections as $col) {
            $col->articleCount = $this->articleRepo->count(['actif' => true, 'collection' => $col]);
        }

        $articles = [];
        if (!empty($collections)) {
            $qb = $this->articleRepo->createQueryBuilder('a')
                ->where('a.actif = true')
                ->andWhere('a.enVedette = true')
                ->andWhere('a.collection IN (:collections)')
                ->setParameter('collections', $collections)
                ->orderBy('a.createdAt', 'DESC')
                ->setMaxResults(8);
            $articles = $qb->getQuery()->getResult();
        }

        return $this->render('public/categorie.html.twig', [
            'category' => $category,
            'collections' => $collections,
            'articles' => $articles
        ]);
    }

    #[Route('/collection/{slug}', name: 'collection')]
    public function collection(string $slug, Request $request): Response
    {
        $collection = $this->collectionRepo->findOneBy(['slug' => $slug, 'actif' => true]);

        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $articles = $this->articleRepo->findBy(
            ['actif' => true, 'collection' => $collection],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );

        $total = $this->articleRepo->count(['actif' => true, 'collection' => $collection]);
        $totalPages = (int)ceil($total / $limit);

        return $this->render('public/collection.html.twig', [
            'collection' => $collection,
            'articles' => $articles,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    #[Route('/article/{slug}', name: 'article_detail')]
    public function articleDetail(string $slug): Response
    {
        $article = $this->articleRepo->findOneBy(['slug' => $slug, 'actif' => true]);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable');
        }

        $collection = $article->getCollection();
        $category = $collection?->getCategorie();

        $similarArticles = [];
        if ($collection) {
            $qb = $this->articleRepo->createQueryBuilder('a')
                ->where('a.actif = true')
                ->andWhere('a.collection = :col')
                ->andWhere('a.id != :id')
                ->setParameter('col', $collection)
                ->setParameter('id', $article->getId())
                ->orderBy('a.createdAt', 'DESC')
                ->setMaxResults(4);
            $similarArticles = $qb->getQuery()->getResult();
        }

        return $this->render('public/article.html.twig', [
            'article' => $article,
            'category' => $category,
            'collection' => $collection,
            'similarArticles' => $similarArticles
        ]);
    }

    #[Route('/panier', name: 'panier')]
    public function panier(): Response
    {
        $fraisVistaprintDomicile = $this->getFraisVistaprintDomicile();
        $total = $this->cartService->getTotal();

        return $this->render('public/panier.html.twig', [
            'items'                   => $this->cartService->getCart(),
            'total'                   => $total,
            'quantity'                => $this->cartService->getTotalQuantity(),
            'grilleTotals'            => $this->cartService->getGrilleTotals(),
            'fraisVistaprintDomicile' => $fraisVistaprintDomicile,
            'totalEstime'             => $total + $fraisVistaprintDomicile,
        ]);
    }

    #[Route('/panier/add', name: 'panier_add', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $articleId = $data['articleId'] ?? null;
        $choices = $data['choices'] ?? [];
        $variantId = $data['variantId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!$articleId) {
            return $this->json(['error' => 'Article ID manquant'], 400);
        }

        $article = $this->articleRepo->find((int)$articleId);

        if (!$article) {
            return $this->json(['error' => 'Article introuvable'], 404);
        }

        $articleArray = [
            'id'             => $article->getId(),
            'nom'            => $article->getNom(),
            'slug'           => $article->getSlug(),
            'prix'           => $article->getPrixBase(),
            'image'          => $article->getFirstImageUrl(),
            'paliers'        => $article->getGrillePrix() ? $article->getGrillePrix()->getPaliers() : [],
            'lignes'         => $article->getGrillePrix() ? $article->getGrillePrix()->getLignes() : [],
            'grilleId'       => $article->getGrillePrix() ? $article->getGrillePrix()->getId() : null,
            'fournisseurNom' => $article->getFournisseur()?->getNom(),
        ];

        // Utiliser le prix de la variante sélectionnée si disponible
        if ($variantId) {
            foreach ($article->getVariantes() as $v) {
                if ($v->getId() == $variantId && $v->getPrix()) {
                    $articleArray['prix'] = $v->getPrix();
                    break;
                }
            }
        }

        $this->cartService->addItem($articleArray, $quantity, is_array($choices) ? $choices : []);

        return $this->json([
            'success' => true,
            'cartCount' => $this->cartService->getTotalQuantity(),
            'message' => 'Article ajouté au panier'
        ]);
    }

    #[Route('/panier/remove/{itemId}', name: 'panier_remove', methods: ['POST'])]
    public function removeFromCart(string $itemId): Response
    {
        $this->cartService->removeItem($itemId);
        $this->addFlash('success', 'Article retiré du panier');
        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/update/{itemId}', name: 'panier_update', methods: ['POST'])]
    public function updateQuantity(string $itemId, Request $request): Response
    {
        $quantity = (int)$request->request->get('quantity', 1);
        $this->cartService->updateQuantity($itemId, $quantity);
        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/clear', name: 'panier_clear', methods: ['POST'])]
    public function clearCart(): Response
    {
        $this->cartService->clear();
        $this->addFlash('success', 'Panier vidé');
        return $this->redirectToRoute('panier');
    }

    #[Route('/checkout/update-livraison', name: 'checkout_update_livraison', methods: ['POST'])]
    public function updateLivraison(Request $request): JsonResponse
    {
        if ($this->cartService->getTotalQuantity() === 0) {
            return $this->json(['error' => 'Panier vide'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $mode = $data['modeLivraison'] ?? 'domicile';
        $livraisonOption = self::LIVRAISON_OPTIONS[$mode] ?? self::LIVRAISON_OPTIONS['domicile'];
        $fraisVistaprint = $mode === 'domicile' ? $this->getFraisVistaprintDomicile() : 0.0;
        $fraisLivraison = $livraisonOption['prix'] + $fraisVistaprint;
        $cartTotal = $this->cartService->getTotal();
        $reduction = $this->getReductionFromCode(
            isset($data['codeReductionId']) ? (int) $data['codeReductionId'] ?: null : null
        );
        $total = max(0.01, $cartTotal + $fraisLivraison - $reduction);

        $paymentIntentId = $data['paymentIntentId'] ?? null;
        if ($paymentIntentId) {
            try {
                $this->stripeService->updatePaymentIntentAmount($paymentIntentId, $total);
            } catch (\Exception $e) {
                // Non-fatal : le montant sera re-validé côté serveur au POST
            }
        }

        return $this->json([
            'fraisLivraison' => $fraisLivraison,
            'reduction'      => $reduction,
            'total'          => $total,
        ]);
    }

    #[Route('/checkout/apply-code', name: 'checkout_apply_code', methods: ['POST'])]
    public function applyCode(Request $request): JsonResponse
    {
        if ($this->cartService->getTotalQuantity() === 0) {
            return $this->json(['error' => 'Panier vide'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $codeReductionId = isset($data['codeReductionId']) ? (int) $data['codeReductionId'] ?: null : null;
        $mode = $data['modeLivraison'] ?? 'domicile';

        $reduction = $this->getReductionFromCode($codeReductionId);
        if ($codeReductionId && $reduction === 0.0) {
            return $this->json(['error' => 'Code invalide ou expiré'], 400);
        }

        $livraisonOption = self::LIVRAISON_OPTIONS[$mode] ?? self::LIVRAISON_OPTIONS['domicile'];
        $fraisVistaprint = $mode === 'domicile' ? $this->getFraisVistaprintDomicile() : 0.0;
        $fraisLivraison = $livraisonOption['prix'] + $fraisVistaprint;
        $cartTotal = $this->cartService->getTotal();
        $total = max(0.01, $cartTotal + $fraisLivraison - $reduction);

        $paymentIntentId = $data['paymentIntentId'] ?? null;
        if ($paymentIntentId) {
            try {
                $this->stripeService->updatePaymentIntentAmount($paymentIntentId, $total);
            } catch (\Exception $e) {
                // Non-fatal
            }
        }

        return $this->json([
            'reduction'      => $reduction,
            'fraisLivraison' => $fraisLivraison,
            'total'          => $total,
        ]);
    }

    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request): Response
    {
        if ($this->cartService->getTotalQuantity() === 0) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('panier');
        }

        $user = $this->getUser();
        $formData = [];
        if ($user instanceof \App\Entity\User) {
            $formData = [
                'prenom'    => $user->getPrenom(),
                'nom'       => $user->getNom(),
                'email'     => $user->getEmail(),
                'telephone' => $user->getTelephone(),
            ];
        }
        $form = $this->createForm(\App\Form\CheckoutType::class, $formData);
        $form->handleRequest($request);

        // POST : vérifier le paiement Stripe et créer la commande
        if ($form->isSubmitted() && $form->isValid()) {
            $paymentIntentId = $request->request->get('stripePaymentIntentId');

            if (!$paymentIntentId) {
                $this->addFlash('error', 'Paiement introuvable. Veuillez recommencer.');
                return $this->redirectToRoute('checkout');
            }

            $modeLivraison = $request->request->get('modeLivraison', 'domicile');
            $livraisonOption = self::LIVRAISON_OPTIONS[$modeLivraison] ?? self::LIVRAISON_OPTIONS['domicile'];
            $fraisVistaprint = $modeLivraison === 'domicile' ? $this->getFraisVistaprintDomicile() : 0.0;
            $fraisLivraison = $livraisonOption['prix'] + $fraisVistaprint;
            $codeReductionId = (int) $request->request->get('codeReductionId') ?: null;
            $reduction = $this->getReductionFromCode($codeReductionId);

            try {
                $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la vérification du paiement.');
                return $this->redirectToRoute('checkout');
            }

            // Vérification du montant côté serveur (inclut frais livraison et réduction)
            $cartTotal = $this->cartService->getTotal();
            $expectedAmount = (int) round(max(0.01, $cartTotal + $fraisLivraison - $reduction) * 100);
            if ($paymentIntent->status !== 'succeeded'
                || $paymentIntent->amount !== $expectedAmount
                || $paymentIntent->currency !== 'eur') {
                $this->addFlash('error', 'Le paiement n\'a pas pu être validé. Veuillez réessayer.');
                return $this->redirectToRoute('checkout');
            }

            // Protection doublon : une commande par PaymentIntent
            $existing = $this->commandeRepo->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
            if ($existing) {
                return $this->redirectToRoute('confirmation', ['id' => $existing->getId()]);
            }

            $data = $form->getData();

            // Construire l'adresse de livraison selon le mode
            $pointRelaisId = (int) $request->request->get('pointRelaisId');
            if ($modeLivraison === 'relais') {
                $relais = ($pointRelaisId ? $this->boutiqueRelaisRepo->find($pointRelaisId) : null)
                    ?? ($this->boutiqueRelaisRepo->findActives()[0] ?? null);
                $adresseLivraison = [
                    'adresse'           => $relais?->getAdresse() ?? '',
                    'complementAdresse' => $relais?->getComplementAdresse() ?? '',
                    'codePostal'        => $relais?->getCodePostal() ?? '',
                    'ville'             => $relais?->getVille() ?? '',
                    'pays'              => 'FR',
                ];
            } else {
                $adresseLivraison = [
                    'adresse'           => $request->request->get('adresse', ''),
                    'complementAdresse' => $request->request->get('complementAdresse', ''),
                    'codePostal'        => $request->request->get('codePostal', ''),
                    'ville'             => $request->request->get('ville', ''),
                    'pays'              => $request->request->get('pays', 'FR'),
                ];
            }

            // Données du mode de livraison
            $modeLivraisonData = [
                'type'  => $modeLivraison,
                'label' => $livraisonOption['label'],
                'prix'  => $fraisLivraison,
            ];
            if ($modeLivraison === 'relais') {
                $modeLivraisonData['pointRelaisNom']     = $relais?->getNom() ?? '';
                $modeLivraisonData['pointRelaisAdresse'] = ($relais?->getAdresse() ?? '') . ', ' . ($relais?->getCodePostal() ?? '') . ' ' . ($relais?->getVille() ?? '');
            }

            $orderItems = [];
            foreach ($this->cartService->getCart() as $itemId => $item) {
                $orderItems[] = [
                    'articleId' => $item['article']['id'],
                    'nom'       => $item['article']['nom'],
                    'prix'      => $item['article']['prix'],
                    'quantity'  => $item['quantity'],
                    'choices'   => $item['choices'] ?? [],
                    'image'     => $item['article']['image'] ?? null,
                ];
            }

            $commande = new Commande();
            $commande->setNumero('CMD-' . strtoupper(uniqid()));
            $commande->setClient([
                'prenom'    => $data['prenom'],
                'nom'       => $data['nom'],
                'email'     => $data['email'],
                'telephone' => $data['telephone'],
            ]);
            $commande->setAdresseLivraison($adresseLivraison);
            $commande->setModeLivraison($modeLivraisonData);
            $commande->setArticles($orderItems);
            $commande->setReduction($reduction);
            $commande->setTotal(max(0.01, $cartTotal + $fraisLivraison - $reduction));
            $commande->setModePaiement('stripe');
            $commande->setNotes($data['notes'] ?? null);
            $commande->setStatut('payee');
            $commande->setStripePaymentIntentId($paymentIntentId);

            $this->em->persist($commande);
            $this->em->flush();

            // Marquer le code de réduction comme utilisé
            if ($codeReductionId) {
                $codeReduction = $this->codeReductionRepo->find($codeReductionId);
                if ($codeReduction && ($codeReduction->isGlobal() || $codeReduction->getUser() === $this->getUser())) {
                    $codeReduction->setStatut('utilise');
                    $codeReduction->setCommande($commande);
                    $this->em->flush();
                }
            }

            $this->cartService->clear();

            return $this->redirectToRoute('confirmation', ['id' => $commande->getId()]);
        }

        // GET : créer un PaymentIntent avec livraison domicile par défaut
        $total = $this->cartService->getTotal();
        $fraisVistaprint = $this->getFraisVistaprintDomicile();
        $fraisParMode = [
            'domicile' => self::LIVRAISON_OPTIONS['domicile']['prix'] + $fraisVistaprint,
            'relais'   => self::LIVRAISON_OPTIONS['relais']['prix'],
            'toulouse' => self::LIVRAISON_OPTIONS['toulouse']['prix'],
        ];
        $totalAvecLivraison = $total + $fraisParMode['domicile'];
        $clientSecret = null;
        $paymentIntentId = null;

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent($totalAvecLivraison, 'ref-' . uniqid());
            $clientSecret = $paymentIntent->client_secret;
            $paymentIntentId = $paymentIntent->id;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Le service de paiement est temporairement indisponible.');
        }

        $codesDisponibles = $this->getUser()
            ? $this->codeReductionRepo->findActiveForUser($this->getUser())
            : [];

        $codesDisponiblesData = array_map(fn ($c) => [
            'id'      => $c->getId(),
            'code'    => $c->getCode(),
            'montant' => $c->getMontant(),
        ], $codesDisponibles);

        return $this->render('public/checkout.html.twig', [
            'form'                 => $form->createView(),
            'cartItems'            => $this->cartService->getCart(),
            'total'                => $total,
            'quantity'             => $this->cartService->getTotalQuantity(),
            'stripePublicKey'      => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
            'clientSecret'         => $clientSecret,
            'paymentIntentId'      => $paymentIntentId,
            'livraisonOptions'     => self::LIVRAISON_OPTIONS,
            'pointsRelais'         => $this->boutiqueRelaisRepo->findActives(),
            'fraisParMode'         => $fraisParMode,
            'codesDisponiblesData' => $codesDisponiblesData,
        ]);
    }

    #[Route('/confirmation/{id}', name: 'confirmation')]
    public function confirmation(int $id): Response
    {
        $commande = $this->commandeRepo->find($id);

        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        return $this->render('public/confirmation.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/commandes/{id}/facture', name: 'commande_facture', requirements: ['id' => '\d+'])]
    public function facture(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $commande = $this->commandeRepo->find($id);

        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        // Vérifier que la commande appartient à l'utilisateur connecté
        $client = $commande->getClient();
        if (($client['email'] ?? '') !== $this->getUser()->getUserIdentifier()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('commandes/facture.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if (!$this->getUser() && $request->isMethod('POST')) {
            $this->addFlash('error', 'Vous devez être connecté pour envoyer un message.');
            return $this->redirectToRoute('connexion', ['redirect' => '/contact']);
        }

        $form = $this->createForm(\App\Form\ContactType::class);

        if ($user = $this->getUser()) {
            $form->get('nom')->setData(trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '')));
            $form->get('email')->setData($user->getEmail());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $message = new Message();
            $message->setNom($data['nom']);
            $message->setEmail($data['email']);
            $message->setSujet($data['sujet'] ?? null);
            $message->setMessage($data['message']);

            $this->em->persist($message);
            $this->em->flush();

            try {
                $sujet = $data['sujet'] ?? 'Sans sujet';
                $corps =
                    "Nouveau message de contact\n\n" .
                    "Nom : " . $data['nom'] . "\n" .
                    "Email : " . $data['email'] . "\n" .
                    "Sujet : " . $sujet . "\n\n" .
                    "Message :\n" . $data['message'];
                $from = $_ENV['MAILER_FROM'] ?? 'tdbrlaboutique@gmail.com';
                $email = (new Email())
                    ->from($from)
                    ->to($from)
                    ->replyTo($data['email'])
                    ->subject('[TDBR Contact] ' . $sujet)
                    ->text($corps);
                $mailer->send($email);
            } catch (\Throwable $e) {
                file_put_contents(
                    __DIR__ . '/../../var/log/mail_error.log',
                    date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }

            $this->addFlash('success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');
            return $this->redirectToRoute('contact');
        }

        return $this->render('public/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/connexion', name: 'connexion', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if ($redirect = $request->query->get('redirect')) {
            if (str_starts_with($redirect, '/')) {
                $request->getSession()->set('_security.main.target_path', $redirect);
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(\App\Form\LoginType::class);

        return $this->render('auth/connexion.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            'last_username' => $lastUsername
        ]);
    }

    #[Route('/inscription', name: 'inscription', methods: ['GET', 'POST'])]
    public function inscription(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(\App\Form\RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $existing = $this->userRepo->findOneBy(['email' => $data['email']]);
            if ($existing) {
                $this->addFlash('error', 'Cet email est déjà utilisé');
                return $this->render('auth/inscription.html.twig', ['form' => $form->createView()]);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setPrenom($data['prenom']);
            $user->setNom($data['nom']);
            $user->setTelephone($data['telephone'] ?? null);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('connexion');
        }

        return $this->render('auth/inscription.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/profil', name: 'profil', methods: ['GET', 'POST'])]
    public function profil(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $prenom = trim($request->request->get('prenom', ''));
            $nom    = trim($request->request->get('nom', ''));
            $email  = trim($request->request->get('email', ''));
            $tel    = trim($request->request->get('telephone', ''));

            if (empty($email)) {
                $this->addFlash('error', 'L\'adresse email est obligatoire.');
            } else {
                $existing = $this->userRepo->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Cette adresse email est déjà utilisée par un autre compte.');
                } else {
                    $user->setPrenom($prenom ?: null);
                    $user->setNom($nom ?: null);
                    $user->setEmail($email);
                    $user->setTelephone($tel ?: null);
                    $this->em->flush();
                    $this->addFlash('success', 'Vos informations ont été mises à jour.');
                }
            }

            return $this->redirectToRoute('profil');
        }

        $userEmail = $user->getUserIdentifier();
        $allCommandes = $this->commandeRepo->findBy([], ['createdAt' => 'DESC']);
        $commandes = array_slice(array_values(array_filter($allCommandes, function($c) use ($userEmail) {
            $client = $c->getClient();
            return ($client['email'] ?? '') === $userEmail;
        })), 0, 10);

        $codesReduction = $this->codeReductionRepo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        // Ajouter les codes globaux actifs
        $codesGlobaux = $this->codeReductionRepo->findActiveGlobal();
        foreach ($codesGlobaux as $cg) {
            $codesReduction[] = $cg;
        }

        return $this->render('auth/profil.html.twig', [
            'user'           => $user,
            'commandes'      => $commandes,
            'codesReduction' => $codesReduction,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Retourne le montant de la réduction si le code est valide pour l'utilisateur connecté.
     */
    private function getReductionFromCode(?int $codeId): float
    {
        if (!$codeId || !$this->getUser()) {
            return 0.0;
        }
        $code = $this->codeReductionRepo->find($codeId);
        if (!$code) {
            return 0.0;
        }
        // Le code doit appartenir à l'utilisateur ou être global
        if (!$code->isGlobal() && $code->getUser() !== $this->getUser()) {
            return 0.0;
        }
        if (!$code->isActif()) {
            return 0.0;
        }
        return $code->getMontant();
    }

    /**
     * Frais de livraison Vistaprint pour le mode domicile.
     * Règle : 5 € si la somme des prix fournisseurs Vistaprint < 50 €, sinon gratuit.
     */
    private function getFraisVistaprintDomicile(): float
    {
        $totalFournisseur = 0.0;
        $hasVistaprint = false;

        foreach ($this->cartService->getCart() as $item) {
            $fournisseurNom = $item['article']['fournisseurNom'] ?? null;
            if ($fournisseurNom && stripos($fournisseurNom, 'vistaprint') !== false) {
                $hasVistaprint = true;
                $qty = (int)($item['quantity'] ?? 1);
                $lignes  = $item['article']['lignes']  ?? [];
                $paliers = $item['article']['paliers'] ?? [];
                $prixLigne = $this->getPrixFournisseurLigne($lignes, $qty);
                if ($prixLigne !== 0.0) {
                    // lignes : prixFournisseur = total pour la quantité (pas unitaire)
                    $totalFournisseur += $prixLigne;
                } else {
                    // paliers : prixFournisseur = prix unitaire
                    $totalFournisseur += $this->getPrixFournisseurPalier($paliers, $qty) * $qty;
                }
            }
        }

        if (!$hasVistaprint) {
            return 0.0;
        }

        return $totalFournisseur >= 50.0 ? 0.0 : 5.0;
    }

    /**
     * Résout le prix fournisseur unitaire depuis les lignes (quantité exacte).
     */
    private function getPrixFournisseurLigne(array $lignes, int $qty): float
    {
        foreach ($lignes as $ligne) {
            if ((int)($ligne['quantite'] ?? 0) === $qty) {
                return (float)($ligne['prixFournisseur'] ?? 0);
            }
        }
        return 0.0;
    }

    /**
     * Résout le prix fournisseur unitaire depuis les paliers pour une quantité donnée.
     */
    private function getPrixFournisseurPalier(array $paliers, int $qty): float
    {
        foreach ($paliers as $palier) {
            if ($qty >= (int)$palier['min']
                && ($palier['max'] === null || $qty <= (int)$palier['max'])) {
                return (float)($palier['prixFournisseur'] ?? 0);
            }
        }
        return 0.0;
    }
}
