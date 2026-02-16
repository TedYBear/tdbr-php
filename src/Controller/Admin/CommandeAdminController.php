<?php

namespace App\Controller\Admin;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

#[Route('/admin/commandes')]
#[IsGranted('ROLE_ADMIN')]
class CommandeAdminController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    #[Route('', name: 'admin_commandes')]
    public function index(Request $request): Response
    {
        $collection = $this->mongoService->getCollection('commandes');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $statut = $request->query->get('statut');
        $filter = [];
        if ($statut) {
            $filter['statut'] = $statut;
        }

        $commandes = $collection->find($filter, [
            'limit' => $limit,
            'skip' => $offset,
            'sort' => ['createdAt' => -1]
        ])->toArray();

        $total = $collection->countDocuments($filter);
        $totalPages = ceil($total / $limit);

        return $this->render('admin/commandes/index.html.twig', [
            'commandes' => $commandes,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'selectedStatut' => $statut
        ]);
    }

    #[Route('/{id}', name: 'admin_commandes_detail')]
    public function detail(string $id): Response
    {
        $commande = $this->mongoService->getCollection('commandes')
            ->findOne(['_id' => new ObjectId($id)]);

        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        return $this->render('admin/commandes/detail.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_commandes_update_status', methods: ['POST'])]
    public function updateStatus(string $id, Request $request): Response
    {
        $statut = $request->request->get('statut');

        $this->mongoService->getCollection('commandes')->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => [
                'statut' => $statut,
                'updatedAt' => new UTCDateTime()
            ]]
        );

        $this->addFlash('success', 'Statut de la commande mis à jour');
        return $this->redirectToRoute('admin_commandes_detail', ['id' => $id]);
    }
}
