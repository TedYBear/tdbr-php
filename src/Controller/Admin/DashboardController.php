<?php

namespace App\Controller\Admin;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Statistiques
        $articlesCount = $this->mongoService->getCollection('articles')->countDocuments(['actif' => true]);
        $categoriesCount = $this->mongoService->getCollection('categories')->countDocuments(['actif' => true]);
        $collectionsCount = $this->mongoService->getCollection('collections')->countDocuments(['actif' => true]);
        $commandesCount = $this->mongoService->getCollection('commandes')->countDocuments([]);

        // Commandes récentes
        $recentCommandes = $this->mongoService->getCollection('commandes')
            ->find([], ['sort' => ['createdAt' => -1], 'limit' => 5])
            ->toArray();

        // Messages non lus
        $messagesNonLus = $this->mongoService->getCollection('messages')
            ->countDocuments(['lu' => false]);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'articles' => $articlesCount,
                'categories' => $categoriesCount,
                'collections' => $collectionsCount,
                'commandes' => $commandesCount,
                'messagesNonLus' => $messagesNonLus
            ],
            'recentCommandes' => $recentCommandes
        ]);
    }
}
