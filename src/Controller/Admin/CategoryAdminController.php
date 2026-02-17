<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\SlugifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepo,
        private SlugifyService $slugify,
    ) {
    }

    #[Route('', name: 'admin_categories')]
    public function index(): Response
    {
        $categories = $this->categoryRepo->findBy([], ['ordre' => 'ASC', 'nom' => 'ASC']);

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/new', name: 'admin_categories_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $category = new Category();
            $category->setNom($data['nom']);
            $category->setSlug($this->slugify->slugify($data['nom']));
            $category->setDescription($data['description'] ?? null);
            $category->setOrdre((int)($data['ordre'] ?? 0));
            $category->setActif(isset($data['actif']));

            $this->em->persist($category);
            $this->em->flush();

            $this->addFlash('success', 'Catégorie créée avec succès');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'category' => null
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_categories_edit')]
    public function edit(int $id, Request $request): Response
    {
        $category = $this->categoryRepo->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $category->setNom($data['nom']);
            $category->setSlug($this->slugify->slugify($data['nom']));
            $category->setDescription($data['description'] ?? null);
            $category->setOrdre((int)($data['ordre'] ?? 0));
            $category->setActif(isset($data['actif']));

            $this->em->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'category' => $category
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_categories_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $category = $this->categoryRepo->find($id);
        if ($category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        $this->addFlash('success', 'Catégorie supprimée avec succès');
        return $this->redirectToRoute('admin_categories');
    }
}
