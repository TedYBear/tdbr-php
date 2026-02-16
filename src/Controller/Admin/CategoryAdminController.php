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

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryAdminController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    #[Route('', name: 'admin_categories')]
    public function index(): Response
    {
        $categories = $this->mongoService->getCollection('categories')
            ->find([], ['sort' => ['ordre' => 1, 'nom' => 1]])
            ->toArray();

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/new', name: 'admin_categories_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $collection = $this->mongoService->getCollection('categories');

            $slug = $this->generateSlug($data['nom']);

            $collection->insertOne([
                'nom' => $data['nom'],
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'ordre' => (int)($data['ordre'] ?? 0),
                'actif' => isset($data['actif']),
                'createdAt' => new UTCDateTime(),
                'updatedAt' => new UTCDateTime()
            ]);

            $this->addFlash('success', 'Catégorie créée avec succès');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'category' => null
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_categories_edit')]
    public function edit(string $id, Request $request): Response
    {
        $collection = $this->mongoService->getCollection('categories');
        $category = $collection->findOne(['_id' => new ObjectId($id)]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $slug = $this->generateSlug($data['nom']);

            $collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => [
                    'nom' => $data['nom'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? '',
                    'ordre' => (int)($data['ordre'] ?? 0),
                    'actif' => isset($data['actif']),
                    'updatedAt' => new UTCDateTime()
                ]]
            );

            $this->addFlash('success', 'Catégorie modifiée avec succès');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'category' => $category
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_categories_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $this->mongoService->getCollection('categories')->deleteOne(['_id' => new ObjectId($id)]);
        $this->addFlash('success', 'Catégorie supprimée avec succès');
        return $this->redirectToRoute('admin_categories');
    }

    private function generateSlug(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
        return trim($text, '-');
    }
}
