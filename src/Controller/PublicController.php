<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProductCollectionRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class PublicController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private CategoryRepository $categoryRepo,
        private ProductCollectionRepository $collectionRepo,
        private CommandeRepository $commandeRepo,
        private UserRepository $userRepo,
        private CartService $cartService,
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
        return $this->render('public/panier.html.twig', [
            'items' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal(),
            'quantity' => $this->cartService->getTotalQuantity()
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
            'id' => $article->getId(),
            'nom' => $article->getNom(),
            'slug' => $article->getSlug(),
            'prix' => $article->getPrixBase(),
            'image' => $article->getFirstImageUrl(),
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

    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request): Response
    {
        if ($this->cartService->getTotalQuantity() === 0) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('panier');
        }

        $form = $this->createForm(\App\Form\CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $orderItems = [];
            foreach ($this->cartService->getCart() as $itemId => $item) {
                $orderItems[] = [
                    'articleId' => $item['article']['id'],
                    'nom' => $item['article']['nom'],
                    'prix' => $item['article']['prix'],
                    'quantity' => $item['quantity'],
                    'choices' => $item['choices'] ?? [],
                    'image' => $item['article']['image'] ?? null
                ];
            }

            $commande = new Commande();
            $commande->setNumero('CMD-' . strtoupper(uniqid()));
            $commande->setClient([
                'prenom' => $data['prenom'],
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
            ]);
            $commande->setAdresseLivraison([
                'adresse' => $data['adresse'],
                'complementAdresse' => $data['complementAdresse'] ?? '',
                'codePostal' => $data['codePostal'],
                'ville' => $data['ville'],
                'pays' => $data['pays'],
            ]);
            $commande->setArticles($orderItems);
            $commande->setTotal($this->cartService->getTotal());
            $commande->setModePaiement($data['modePaiement']);
            $commande->setNotes($data['notes'] ?? null);
            $commande->setStatut('en_attente');

            $this->em->persist($commande);
            $this->em->flush();

            $this->cartService->clear();

            return $this->redirectToRoute('confirmation', ['id' => $commande->getId()]);
        }

        return $this->render('public/checkout.html.twig', [
            'form' => $form->createView(),
            'cartItems' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal(),
            'quantity' => $this->cartService->getTotalQuantity()
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

        return $this->render('auth/profil.html.twig', [
            'user' => $user,
            'commandes' => $commandes
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
