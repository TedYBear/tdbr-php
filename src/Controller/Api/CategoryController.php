<?php

namespace App\Controller\Api;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/categories', name: 'api_category_')]
class CategoryController extends AbstractController
{
    private MongoDBService $mongoService;

    public function __construct(MongoDBService $mongoService)
    {
        $this->mongoService = $mongoService;
    }

    /**
     * Get all active categories (public)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        try {
            $collection = $this->mongoService->getCollection('categories');
            $categories = $collection->find(
                ['actif' => true],
                ['sort' => ['ordre' => 1]]
            )->toArray();

            return $this->json(array_values($categories));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get category by slug (public)
     */
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function getCategoryBySlug(string $slug): JsonResponse
    {
        try {
            $collection = $this->mongoService->getCollection('categories');
            $category = $collection->findOne([
                'slug' => $slug,
                'actif' => true
            ]);

            if (!$category) {
                return $this->json(
                    ['error' => 'Catégorie non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json($category);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all categories including inactive (admin)
     */
    #[Route('/admin/all', name: 'admin_list', methods: ['GET'])]
    public function getAllCategories(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('categories');
            $categories = $collection->find(
                [],
                ['sort' => ['ordre' => 1]]
            )->toArray();

            return $this->json(array_values($categories));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get category by ID (admin)
     */
    #[Route('/admin/{id}', name: 'admin_show', methods: ['GET'])]
    public function getCategoryById(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('categories');
            $category = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if (!$category) {
                return $this->json(
                    ['error' => 'Catégorie non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json($category);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create category (admin)
     */
    #[Route('/admin', name: 'admin_create', methods: ['POST'])]
    public function createCategory(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            // Validation basique
            if (!isset($data['nom']) || !isset($data['slug'])) {
                return $this->json(
                    ['error' => 'Nom et slug requis'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérifier que le slug n'existe pas déjà
            $collection = $this->mongoService->getCollection('categories');
            $existing = $collection->findOne(['slug' => $data['slug']]);

            if ($existing) {
                return $this->json(
                    ['error' => 'Une catégorie avec ce slug existe déjà'],
                    Response::HTTP_CONFLICT
                );
            }

            // Préparer les données
            $categoryData = [
                'nom' => $data['nom'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'actif' => $data['actif'] ?? true,
                'ordre' => $data['ordre'] ?? 0,
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $collection->insertOne($categoryData);
            $categoryData['_id'] = $result->getInsertedId();

            return $this->json($categoryData, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update category (admin)
     */
    #[Route('/admin/{id}', name: 'admin_update', methods: ['PUT'])]
    public function updateCategory(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $collection = $this->mongoService->getCollection('categories');

            // Vérifier que la catégorie existe
            $category = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$category) {
                return $this->json(
                    ['error' => 'Catégorie non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Préparer les données de mise à jour
            $updateData = [
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            if (isset($data['nom'])) $updateData['nom'] = $data['nom'];
            if (isset($data['slug'])) $updateData['slug'] = $data['slug'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['actif'])) $updateData['actif'] = $data['actif'];
            if (isset($data['ordre'])) $updateData['ordre'] = $data['ordre'];

            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => $updateData]
            );

            $updatedCategory = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            return $this->json($updatedCategory);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete category (admin)
     */
    #[Route('/admin/{id}', name: 'admin_delete', methods: ['DELETE'])]
    public function deleteCategory(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('categories');

            $result = $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if ($result->getDeletedCount() === 0) {
                return $this->json(
                    ['error' => 'Catégorie non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json(['message' => 'Catégorie supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
