<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductCollectionRepository;
use App\Repository\CommandeRepository;
use App\Repository\DevisRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepo,
        private CategoryRepository $categoryRepo,
        private ProductCollectionRepository $collectionRepo,
        private CommandeRepository $commandeRepo,
        private DevisRepository $devisRepo,
        private MessageRepository $messageRepo,
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function index(): Response
    {
        $articlesCount = $this->articleRepo->count(['actif' => true]);
        $categoriesCount = $this->categoryRepo->count(['actif' => true]);
        $collectionsCount = $this->collectionRepo->count(['actif' => true]);
        $commandesCount = $this->commandeRepo->count([]);
        $devisCount = $this->devisRepo->count([]);
        $messagesNonLus = $this->messageRepo->count(['lu' => false]);

        $recentCommandes = $this->commandeRepo->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'articles' => $articlesCount,
                'categories' => $categoriesCount,
                'collections' => $collectionsCount,
                'commandes' => $commandesCount,
                'devis' => $devisCount,
                'messagesNonLus' => $messagesNonLus
            ],
            'recentCommandes' => $recentCommandes
        ]);
    }
}
