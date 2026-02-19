<?php
namespace App\Controller\Admin;

use App\Repository\DevisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/devis')]
class DevisAdminController extends AbstractController
{
    public function __construct(
        private DevisRepository $devisRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'admin_devis')]
    public function index(): Response
    {
        $devis = $this->devisRepo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/devis/index.html.twig', ['devis' => $devis]);
    }

    #[Route('/{id}', name: 'admin_devis_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $devis = $this->devisRepo->find($id);
        if (!$devis) throw $this->createNotFoundException();
        return $this->render('admin/devis/detail.html.twig', ['devis' => $devis]);
    }

    #[Route('/{id}/statut', name: 'admin_devis_statut', methods: ['POST'])]
    public function updateStatut(int $id, Request $request): Response
    {
        $devis = $this->devisRepo->find($id);
        if (!$devis) throw $this->createNotFoundException();

        $statut = $request->request->get('statut');
        $statutsValides = ['nouveau', 'en_cours', 'envoye', 'accepte', 'refuse'];
        if (in_array($statut, $statutsValides)) {
            $devis->setStatut($statut);
            $devis->setUpdatedAt(new \DateTimeImmutable());
        }

        $notes = $request->request->get('notesAdmin');
        if ($notes !== null) {
            $devis->setNotesAdmin($notes ?: null);
        }

        $this->em->flush();
        $this->addFlash('success', 'Devis mis à jour.');
        return $this->redirectToRoute('admin_devis_detail', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'admin_devis_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $devis = $this->devisRepo->find($id);
        if ($devis) {
            $this->em->remove($devis);
            $this->em->flush();
            $this->addFlash('success', 'Devis supprimé.');
        }
        return $this->redirectToRoute('admin_devis');
    }
}
