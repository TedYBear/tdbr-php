<?php

namespace App\Controller\Admin;

use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/commandes')]
#[IsGranted('ROLE_ADMIN')]
class CommandeAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CommandeRepository $commandeRepo,
    ) {
    }

    #[Route('', name: 'admin_commandes')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $statut = $request->query->get('statut');
        $criteria = $statut ? ['statut' => $statut] : [];

        $commandes = $this->commandeRepo->findBy($criteria, ['createdAt' => 'DESC'], $limit, $offset);
        $total = $this->commandeRepo->count($criteria);
        $totalPages = (int)ceil($total / $limit);

        return $this->render('admin/commandes/index.html.twig', [
            'commandes' => $commandes,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'selectedStatut' => $statut
        ]);
    }

    #[Route('/{id}', name: 'admin_commandes_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id): Response
    {
        $commande = $this->commandeRepo->find($id);

        if (!$commande) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        return $this->render('admin/commandes/detail.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_commandes_update_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $commande = $this->commandeRepo->find($id);

        if ($commande) {
            $statut = $request->request->get('statut');
            $commande->setStatut($statut);
            $commande->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        $this->addFlash('success', 'Statut de la commande mis à jour');
        return $this->redirectToRoute('admin_commandes_detail', ['id' => $id]);
    }
}
