<?php

namespace App\Controller;

use App\Service\MongoDBService;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService,
        private CartService $cartService
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

    #[Route('/catalogue', name: 'catalogue')]
    public function catalogue(Request $request): Response
    {
        $collection = $this->mongoService->getCollection('articles');
        $categoriesCollection = $this->mongoService->getCollection('categories');

        // Filtres
        $categoryId = $request->query->get('categorie');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Construction du filtre
        $filter = ['actif' => true];
        if ($categoryId) {
            try {
                $filter['categorieId'] = new \MongoDB\BSON\ObjectId($categoryId);
            } catch (\Exception $e) {
                // ID invalide, on ignore le filtre
            }
        }

        // Récupération des articles
        $articles = $collection->find($filter, [
            'limit' => $limit,
            'skip' => $offset,
            'sort' => ['createdAt' => -1]
        ])->toArray();

        $total = $collection->countDocuments($filter);
        $totalPages = ceil($total / $limit);

        // Récupération des catégories pour les filtres
        $categories = $categoriesCollection->find(['actif' => true], [
            'sort' => ['ordre' => 1]
        ])->toArray();

        return $this->render('public/catalogue.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'selectedCategory' => $categoryId
        ]);
    }

    #[Route('/categorie/{slug}', name: 'categorie')]
    public function categorie(string $slug, Request $request): Response
    {
        $categoriesCollection = $this->mongoService->getCollection('categories');
        $articlesCollection = $this->mongoService->getCollection('articles');

        // Récupérer la catégorie
        $category = $categoriesCollection->findOne(['slug' => $slug, 'actif' => true]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable');
        }

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Récupérer les articles de cette catégorie
        $articles = $articlesCollection->find([
            'categorieId' => $category['_id'],
            'actif' => true
        ], [
            'limit' => $limit,
            'skip' => $offset,
            'sort' => ['createdAt' => -1]
        ])->toArray();

        $total = $articlesCollection->countDocuments([
            'categorieId' => $category['_id'],
            'actif' => true
        ]);
        $totalPages = ceil($total / $limit);

        return $this->render('public/categorie.html.twig', [
            'category' => $category,
            'articles' => $articles,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    #[Route('/collection/{slug}', name: 'collection')]
    public function collection(string $slug, Request $request): Response
    {
        $collectionsCollection = $this->mongoService->getCollection('collections');
        $articlesCollection = $this->mongoService->getCollection('articles');

        // Récupérer la collection
        $collection = $collectionsCollection->findOne(['slug' => $slug, 'actif' => true]);

        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable');
        }

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Récupérer les articles de cette collection
        $articles = $articlesCollection->find([
            'collectionId' => $collection['_id'],
            'actif' => true
        ], [
            'limit' => $limit,
            'skip' => $offset,
            'sort' => ['createdAt' => -1]
        ])->toArray();

        $total = $articlesCollection->countDocuments([
            'collectionId' => $collection['_id'],
            'actif' => true
        ]);
        $totalPages = ceil($total / $limit);

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
        $articlesCollection = $this->mongoService->getCollection('articles');
        $categoriesCollection = $this->mongoService->getCollection('categories');
        $collectionsCollection = $this->mongoService->getCollection('collections');

        // Récupérer l'article
        $article = $articlesCollection->findOne(['slug' => $slug, 'actif' => true]);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable');
        }

        // Récupérer la catégorie de l'article
        $category = null;
        if (isset($article['categorieId'])) {
            $category = $categoriesCollection->findOne(['_id' => $article['categorieId']]);
        }

        // Récupérer la collection de l'article
        $collection = null;
        if (isset($article['collectionId'])) {
            $collection = $collectionsCollection->findOne(['_id' => $article['collectionId']]);
        }

        // Articles similaires (même catégorie, 4 articles max)
        $similarArticles = [];
        if (isset($article['categorieId'])) {
            $similarArticles = $articlesCollection->find([
                'categorieId' => $article['categorieId'],
                'actif' => true,
                '_id' => ['$ne' => $article['_id']]
            ], [
                'limit' => 4,
                'sort' => ['createdAt' => -1]
            ])->toArray();
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
        $variantId = $data['variantId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!$articleId) {
            return $this->json(['error' => 'Article ID manquant'], 400);
        }

        // Charger l'article depuis MongoDB
        $collection = $this->mongoService->getCollection('articles');
        try {
            $article = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($articleId)]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Article invalide'], 400);
        }

        if (!$article) {
            return $this->json(['error' => 'Article introuvable'], 404);
        }

        // Trouver la variante si nécessaire
        $variant = null;
        if ($variantId && isset($article['variantes'])) {
            foreach ($article['variantes'] as $v) {
                if (($v['id'] ?? $v['_id'] ?? null) == $variantId) {
                    $variant = (array)$v;
                    break;
                }
            }
        }

        $this->cartService->addItem((array)$article, $quantity, $variant);

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
        // Vérifier que le panier n'est pas vide
        if ($this->cartService->getTotalQuantity() === 0) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('panier');
        }

        $form = $this->createForm(\App\Form\CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Préparer les articles de la commande
            $orderItems = [];
            $cartItems = $this->cartService->getCart();
            foreach ($cartItems as $itemId => $item) {
                $orderItems[] = [
                    'articleId' => $item['article']['_id'],
                    'nom' => $item['article']['nom'],
                    'prix' => $item['variant']['prix'] ?? $item['article']['prix'],
                    'quantity' => $item['quantity'],
                    'variant' => $item['variant'] ? [
                        'id' => $item['variant']['id'],
                        'nom' => $item['variant']['nom'] ?? 'Standard'
                    ] : null,
                    'image' => $item['article']['images'][0] ?? null
                ];
            }

            // Créer la commande dans MongoDB
            $commandesCollection = $this->mongoService->getCollection('commandes');
            $result = $commandesCollection->insertOne([
                'numero' => 'CMD-' . strtoupper(uniqid()),
                'client' => [
                    'prenom' => $data['prenom'],
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'telephone' => $data['telephone']
                ],
                'adresseLivraison' => [
                    'adresse' => $data['adresse'],
                    'complementAdresse' => $data['complementAdresse'],
                    'codePostal' => $data['codePostal'],
                    'ville' => $data['ville'],
                    'pays' => $data['pays']
                ],
                'articles' => $orderItems,
                'total' => $this->cartService->getTotal(),
                'modePaiement' => $data['modePaiement'],
                'notes' => $data['notes'],
                'statut' => 'en_attente',
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ]);

            // Vider le panier
            $this->cartService->clear();

            // Rediriger vers la page de confirmation
            return $this->redirectToRoute('confirmation', [
                'id' => (string)$result->getInsertedId()
            ]);
        }

        return $this->render('public/checkout.html.twig', [
            'form' => $form->createView(),
            'cartItems' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal(),
            'quantity' => $this->cartService->getTotalQuantity()
        ]);
    }

    #[Route('/confirmation/{id}', name: 'confirmation')]
    public function confirmation(string $id): Response
    {
        $commandesCollection = $this->mongoService->getCollection('commandes');

        try {
            $commande = $commandesCollection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        return $this->render('public/confirmation.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(\App\Form\ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Sauvegarder le message dans MongoDB
            $collection = $this->mongoService->getCollection('messages');
            $collection->insertOne([
                'nom' => $data['nom'],
                'email' => $data['email'],
                'sujet' => $data['sujet'],
                'message' => $data['message'],
                'lu' => false,
                'createdAt' => new \MongoDB\BSON\UTCDateTime()
            ]);

            $this->addFlash('success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');
            return $this->redirectToRoute('contact');
        }

        return $this->render('public/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/connexion', name: 'connexion', methods: ['GET', 'POST'])]
    public function connexion(Request $request, \Symfony\Component\Security\Http\Authentication\AuthenticationUtils $authenticationUtils): Response
    {
        // Si déjà connecté, rediriger vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier nom d'utilisateur saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(\App\Form\LoginType::class, [
            'email' => $lastUsername
        ]);

        return $this->render('auth/connexion.html.twig', [
            'form' => $form->createView(),
            'error' => $error
        ]);
    }

    #[Route('/inscription', name: 'inscription', methods: ['GET', 'POST'])]
    public function inscription(Request $request, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        // Si déjà connecté, rediriger vers l'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(\App\Form\RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérifier si l'email existe déjà
            $utilisateursCollection = $this->mongoService->getCollection('utilisateurs');
            $existingUser = $utilisateursCollection->findOne(['email' => $data['email']]);

            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé');
                return $this->render('auth/inscription.html.twig', [
                    'form' => $form->createView()
                ]);
            }

            // Hasher le mot de passe
            $user = new \App\Entity\User(
                '',
                $data['email'],
                '',
                ['ROLE_USER'],
                $data['prenom'],
                $data['nom'],
                $data['telephone'] ?? null
            );

            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);

            // Créer l'utilisateur dans MongoDB
            $utilisateursCollection->insertOne([
                'prenom' => $data['prenom'],
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'] ?? null,
                'password' => $hashedPassword,
                'roles' => ['ROLE_USER'],
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ]);

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('connexion');
        }

        return $this->render('auth/inscription.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/profil', name: 'profil')]
    public function profil(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        // Récupérer les commandes de l'utilisateur
        $commandesCollection = $this->mongoService->getCollection('commandes');
        $commandes = $commandesCollection->find(
            ['client.email' => $user->getUserIdentifier()],
            ['sort' => ['createdAt' => -1], 'limit' => 10]
        )->toArray();

        return $this->render('auth/profil.html.twig', [
            'user' => $user,
            'commandes' => $commandes
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        // Cette méthode peut rester vide, elle sera interceptée par le firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function adminDashboard(): Response
    {
        return new Response('<h1>Admin Dashboard - À venir</h1>');
    }
}
