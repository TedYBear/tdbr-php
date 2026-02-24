<?php

namespace App\Controller\Admin;

use App\Entity\GrillePrix;
use App\Repository\GrillePrixRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/grilles-prix')]
#[IsGranted('ROLE_ADMIN')]
class GrillePrixAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GrillePrixRepository $repo,
    ) {
    }

    #[Route('', name: 'admin_grilles_prix')]
    public function index(): Response
    {
        return $this->render('admin/grilles_prix/index.html.twig', [
            'grilles' => $this->repo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_grilles_prix_new')]
    public function new(Request $request): Response
    {
        $grille = new GrillePrix();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $grille->setNom($data['nom']);
            $grille->setDescription($data['description'] ?? null);
            $grille->setPaliers($this->buildPaliers($data['paliers'] ?? []));
            $grille->setLignes($this->buildLignes($data['lignes'] ?? []));

            $this->em->persist($grille);
            $this->em->flush();

            $this->addFlash('success', 'Grille de prix créée avec succès');
            return $this->redirectToRoute('admin_grilles_prix');
        }

        return $this->render('admin/grilles_prix/form.html.twig', ['grille' => $grille]);
    }

    #[Route('/{id}/edit', name: 'admin_grilles_prix_edit')]
    public function edit(int $id, Request $request): Response
    {
        $grille = $this->repo->find($id);

        if (!$grille) {
            throw $this->createNotFoundException('Grille introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $grille->setNom($data['nom']);
            $grille->setDescription($data['description'] ?? null);
            $grille->setPaliers($this->buildPaliers($data['paliers'] ?? []));
            $grille->setLignes($this->buildLignes($data['lignes'] ?? []));

            $this->em->flush();

            $this->addFlash('success', 'Grille de prix modifiée avec succès');
            return $this->redirectToRoute('admin_grilles_prix');
        }

        return $this->render('admin/grilles_prix/form.html.twig', ['grille' => $grille]);
    }

    #[Route('/{id}/clone', name: 'admin_grilles_prix_clone', methods: ['POST'])]
    public function clone(int $id): Response
    {
        $source = $this->repo->find($id);
        if (!$source) {
            throw $this->createNotFoundException('Grille introuvable');
        }

        $clone = new GrillePrix();
        $clone->setNom($source->getNom() . ' (copie)');
        $clone->setDescription($source->getDescription());
        $clone->setPaliers($source->getPaliers());
        $clone->setLignes($source->getLignes());

        $this->em->persist($clone);
        $this->em->flush();

        $this->addFlash('success', 'Grille clonée avec succès');
        return $this->redirectToRoute('admin_grilles_prix_edit', ['id' => $clone->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_grilles_prix_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $grille = $this->repo->find($id);
        if ($grille) {
            $this->em->remove($grille);
            $this->em->flush();
        }

        $this->addFlash('success', 'Grille de prix supprimée');
        return $this->redirectToRoute('admin_grilles_prix');
    }

    /**
     * Construit les 4 paliers tarifaires (prix unitaires) depuis les données du formulaire.
     */
    private function buildPaliers(array $raw): array
    {
        $defaults = [
            ['label' => '1 exemplaire',        'min' => 1,  'max' => 1],
            ['label' => '2 à 4 exemplaires',   'min' => 2,  'max' => 4],
            ['label' => '5 à 9 exemplaires',   'min' => 5,  'max' => 9],
            ['label' => '10 exemplaires et +', 'min' => 10, 'max' => null],
        ];
        $paliers = [];
        for ($i = 0; $i < 4; $i++) {
            $row = $raw[$i] ?? [];
            $paliers[] = [
                'label'           => $defaults[$i]['label'],
                'min'             => $defaults[$i]['min'],
                'max'             => $defaults[$i]['max'],
                'prixFournisseur' => isset($row['prixFournisseur']) && $row['prixFournisseur'] !== '' ? (float)$row['prixFournisseur'] : null,
                'prixVente'       => isset($row['prixVente'])       && $row['prixVente']       !== '' ? (float)$row['prixVente']       : null,
            ];
        }
        return $paliers;
    }

    /**
     * Construit un tableau de 10 lignes propres depuis les données du formulaire.
     */
    private function buildLignes(array $raw): array
    {
        $lignes = [];
        for ($i = 1; $i <= 10; $i++) {
            $row = $raw[$i - 1] ?? [];
            $lignes[] = [
                'quantite'        => $i,
                'prixFournisseur' => isset($row['prixFournisseur']) && $row['prixFournisseur'] !== '' ? (float)$row['prixFournisseur'] : null,
                'prixVente'       => isset($row['prixVente'])       && $row['prixVente']       !== '' ? (float)$row['prixVente']       : null,
            ];
        }
        return $lignes;
    }
}
