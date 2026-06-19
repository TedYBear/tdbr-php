<?php

namespace App\Controller\Admin;

use App\Entity\TypePersonnalisation;
use App\Repository\TypePersonnalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/personnalisations')]
#[IsGranted('ROLE_ADMIN')]
class PersonnalisationAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TypePersonnalisationRepository $repo,
    ) {
    }

    #[Route('', name: 'admin_personnalisations')]
    public function index(): Response
    {
        $personnalisations = $this->repo->findBy([], ['ordre' => 'ASC', 'nom' => 'ASC']);

        return $this->render('admin/personnalisations/index.html.twig', [
            'personnalisations' => $personnalisations,
        ]);
    }

    #[Route('/new', name: 'admin_personnalisations_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $perso = new TypePersonnalisation();
            $perso->setNom(trim($data['nom'] ?? ''));
            $perso->setActif(isset($data['actif']));
            $perso->setOrdre((int) ($data['ordre'] ?? 0));

            $this->em->persist($perso);
            $this->em->flush();

            $this->addFlash('success', 'Personnalisation créée avec succès');
            return $this->redirectToRoute('admin_personnalisations');
        }

        return $this->render('admin/personnalisations/form.html.twig', [
            'personnalisation' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_personnalisations_edit')]
    public function edit(int $id, Request $request): Response
    {
        $perso = $this->repo->find($id);
        if (!$perso) {
            throw $this->createNotFoundException('Personnalisation introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $perso->setNom(trim($data['nom'] ?? ''));
            $perso->setActif(isset($data['actif']));
            $perso->setOrdre((int) ($data['ordre'] ?? 0));

            $this->em->flush();

            $this->addFlash('success', 'Personnalisation modifiée avec succès');
            return $this->redirectToRoute('admin_personnalisations');
        }

        return $this->render('admin/personnalisations/form.html.twig', [
            'personnalisation' => $perso,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_personnalisations_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $perso = $this->repo->find($id);
        if ($perso) {
            $this->em->remove($perso);
            $this->em->flush();
        }

        $this->addFlash('success', 'Personnalisation supprimée');
        return $this->redirectToRoute('admin_personnalisations');
    }
}
