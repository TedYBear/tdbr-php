<?php

namespace App\Controller\Admin;

use App\Entity\BoutiqueRelais;
use App\Repository\BoutiqueRelaisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boutiques-relais')]
#[IsGranted('ROLE_ADMIN')]
class BoutiqueRelaisAdminController extends AbstractController
{
    public function __construct(
        private BoutiqueRelaisRepository $repo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'admin_boutiques_relais', methods: ['GET'])]
    public function index(): Response
    {
        $boutiques = $this->repo->findBy([], ['ville' => 'ASC', 'nom' => 'ASC']);

        return $this->render('admin/boutique_relais/index.html.twig', [
            'boutiques' => $boutiques,
            'total' => count($boutiques),
        ]);
    }

    #[Route('/new', name: 'admin_boutiques_relais_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $boutique = new BoutiqueRelais();
            $this->hydrateFromRequest($boutique, $request);
            $this->em->persist($boutique);
            $this->em->flush();

            $this->addFlash('success', 'Boutique relais créée.');
            return $this->redirectToRoute('admin_boutiques_relais');
        }

        return $this->render('admin/boutique_relais/form.html.twig', [
            'boutique' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_boutiques_relais_edit', methods: ['GET', 'POST'])]
    public function edit(BoutiqueRelais $boutique, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->hydrateFromRequest($boutique, $request);
            $this->em->flush();

            $this->addFlash('success', 'Boutique relais mise à jour.');
            return $this->redirectToRoute('admin_boutiques_relais');
        }

        return $this->render('admin/boutique_relais/form.html.twig', [
            'boutique' => $boutique,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_boutiques_relais_toggle', methods: ['POST'])]
    public function toggle(BoutiqueRelais $boutique): Response
    {
        $boutique->setActif(!$boutique->isActif());
        $this->em->flush();

        $etat = $boutique->isActif() ? 'activée' : 'désactivée';
        $this->addFlash('success', "Boutique {$etat}.");
        return $this->redirectToRoute('admin_boutiques_relais');
    }

    #[Route('/{id}/delete', name: 'admin_boutiques_relais_delete', methods: ['POST'])]
    public function delete(BoutiqueRelais $boutique): Response
    {
        $this->em->remove($boutique);
        $this->em->flush();

        $this->addFlash('success', 'Boutique supprimée.');
        return $this->redirectToRoute('admin_boutiques_relais');
    }

    private function hydrateFromRequest(BoutiqueRelais $boutique, Request $request): void
    {
        $boutique
            ->setNom(trim($request->request->get('nom', '')))
            ->setAdresse(trim($request->request->get('adresse', '')))
            ->setComplementAdresse(trim($request->request->get('complementAdresse', '')) ?: null)
            ->setCodePostal(trim($request->request->get('codePostal', '')))
            ->setVille(trim($request->request->get('ville', '')))
            ->setTelephone(trim($request->request->get('telephone', '')) ?: null)
            ->setEmail(trim($request->request->get('email', '')) ?: null)
            ->setActif($request->request->has('actif'));
    }
}
