<?php

namespace App\Controller\Admin;

use App\Entity\Fournisseur;
use App\Repository\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/fournisseurs')]
#[IsGranted('ROLE_ADMIN')]
class FournisseurAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FournisseurRepository $fournisseurRepo,
    ) {}

    #[Route('', name: 'admin_fournisseurs')]
    public function index(): Response
    {
        $fournisseurs = $this->fournisseurRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/fournisseurs/index.html.twig', [
            'fournisseurs' => $fournisseurs,
        ]);
    }

    #[Route('/new', name: 'admin_fournisseurs_new')]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $fournisseur = new Fournisseur();
            $fournisseur->setNom($data['nom']);
            $fournisseur->setUrl(!empty($data['url']) ? $data['url'] : null);

            $logoFile = $request->files->get('logo');
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/fournisseurs',
                        $newFilename
                    );
                    $fournisseur->setLogoFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo');
                }
            }

            $this->em->persist($fournisseur);
            $this->em->flush();

            $this->addFlash('success', 'Fournisseur créé avec succès');
            return $this->redirectToRoute('admin_fournisseurs');
        }

        return $this->render('admin/fournisseurs/form.html.twig', [
            'fournisseur' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_fournisseurs_edit')]
    public function edit(int $id, Request $request, SluggerInterface $slugger): Response
    {
        $fournisseur = $this->fournisseurRepo->find($id);
        if (!$fournisseur) {
            throw $this->createNotFoundException('Fournisseur introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $fournisseur->setNom($data['nom']);
            $fournisseur->setUrl(!empty($data['url']) ? $data['url'] : null);

            $logoFile = $request->files->get('logo');
            if ($logoFile) {
                // Supprimer ancien logo
                if ($fournisseur->getLogoFilename()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/fournisseurs/' . $fournisseur->getLogoFilename();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/fournisseurs',
                        $newFilename
                    );
                    $fournisseur->setLogoFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo');
                }
            }

            $this->em->flush();

            $this->addFlash('success', 'Fournisseur modifié avec succès');
            return $this->redirectToRoute('admin_fournisseurs');
        }

        return $this->render('admin/fournisseurs/form.html.twig', [
            'fournisseur' => $fournisseur,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_fournisseurs_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $fournisseur = $this->fournisseurRepo->find($id);
        if ($fournisseur) {
            if ($fournisseur->getLogoFilename()) {
                $file = $this->getParameter('kernel.project_dir') . '/public/uploads/fournisseurs/' . $fournisseur->getLogoFilename();
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $this->em->remove($fournisseur);
            $this->em->flush();
        }

        $this->addFlash('success', 'Fournisseur supprimé');
        return $this->redirectToRoute('admin_fournisseurs');
    }
}
