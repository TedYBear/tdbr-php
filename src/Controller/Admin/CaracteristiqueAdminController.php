<?php

namespace App\Controller\Admin;

use App\Entity\Caracteristique;
use App\Repository\CaracteristiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/caracteristiques')]
#[IsGranted('ROLE_ADMIN')]
class CaracteristiqueAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CaracteristiqueRepository $caracRepo,
    ) {
    }

    #[Route('', name: 'admin_caracteristiques')]
    public function index(): Response
    {
        $caracteristiques = $this->caracRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/caracteristiques/index.html.twig', [
            'caracteristiques' => $caracteristiques
        ]);
    }

    #[Route('/new', name: 'admin_caracteristiques_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $valeurs = array_filter(
                array_map('trim', (array)($data['valeurs'] ?? [])),
                fn($v) => $v !== ''
            );

            $carac = new Caracteristique();
            $carac->setNom(trim($data['nom']));
            $carac->setType($data['type'] ?? 'text');
            $carac->setObligatoire(isset($data['obligatoire']));
            $carac->setValeursFromArray(array_values($valeurs));

            $this->em->persist($carac);
            $this->em->flush();

            $this->addFlash('success', 'Caractéristique créée avec succès');
            return $this->redirectToRoute('admin_caracteristiques');
        }

        return $this->render('admin/caracteristiques/form.html.twig', [
            'caracteristique' => null
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_caracteristiques_edit')]
    public function edit(int $id, Request $request): Response
    {
        $carac = $this->caracRepo->find($id);

        if (!$carac) {
            throw $this->createNotFoundException('Caractéristique introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $valeurs = array_filter(
                array_map('trim', (array)($data['valeurs'] ?? [])),
                fn($v) => $v !== ''
            );

            $carac->setNom(trim($data['nom']));
            $carac->setType($data['type'] ?? 'text');
            $carac->setObligatoire(isset($data['obligatoire']));
            $carac->setValeursFromArray(array_values($valeurs));

            $this->em->flush();

            $this->addFlash('success', 'Caractéristique modifiée avec succès');
            return $this->redirectToRoute('admin_caracteristiques');
        }

        return $this->render('admin/caracteristiques/form.html.twig', [
            'caracteristique' => $carac
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_caracteristiques_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $carac = $this->caracRepo->find($id);
        if ($carac) {
            $this->em->remove($carac);
            $this->em->flush();
        }

        $this->addFlash('success', 'Caractéristique supprimée');
        return $this->redirectToRoute('admin_caracteristiques');
    }
}
