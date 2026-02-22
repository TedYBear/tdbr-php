<?php
namespace App\Controller\Admin;

use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AvisAdminController extends AbstractController
{
    public function __construct(
        private AvisRepository $avisRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/admin/avis', name: 'admin_avis')]
    public function index(): Response
    {
        return $this->render('admin/avis/index.html.twig', [
            'avis' => $this->avisRepo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/avis/{id}/valider', name: 'admin_avis_valider', methods: ['POST'])]
    public function valider(int $id): Response
    {
        $avis = $this->avisRepo->find($id);
        if ($avis) {
            $avis->setVisible(true);
            $this->em->flush();
            $this->addFlash('success', 'Avis validé et publié.');
        }
        return $this->redirectToRoute('admin_avis');
    }

    #[Route('/admin/avis/{id}/masquer', name: 'admin_avis_masquer', methods: ['POST'])]
    public function masquer(int $id): Response
    {
        $avis = $this->avisRepo->find($id);
        if ($avis) {
            $avis->setVisible(false);
            $this->em->flush();
            $this->addFlash('success', 'Avis masqué.');
        }
        return $this->redirectToRoute('admin_avis');
    }

    #[Route('/admin/avis/{id}/ordre', name: 'admin_avis_ordre', methods: ['POST'])]
    public function ordre(int $id, Request $request): Response
    {
        $avis = $this->avisRepo->find($id);
        if ($avis) {
            $ordre = $request->request->get('ordre');
            $avis->setOrdre($ordre !== '' && $ordre !== null ? (int)$ordre : null);
            $this->em->flush();
        }
        return $this->redirectToRoute('admin_avis');
    }

    #[Route('/admin/avis/{id}/delete', name: 'admin_avis_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $avis = $this->avisRepo->find($id);
        if ($avis) {
            if ($avis->getPhotoFilename()) {
                $path = $this->getParameter('kernel.project_dir') . '/public/uploads/avis/' . $avis->getPhotoFilename();
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            $this->em->remove($avis);
            $this->em->flush();
            $this->addFlash('success', 'Avis supprimé.');
        }
        return $this->redirectToRoute('admin_avis');
    }
}
