<?php

namespace App\Controller\Api;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/articles', name: 'api_article_')]
class ArticleController extends AbstractController
{
    private MongoDBService $mongoService;

    public function __construct(MongoDBService $mongoService)
    {
        $this->mongoService = $mongoService;
    }

    /**
     * Get all active articles (public)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function getArticles(Request $request): JsonResponse
    {
        try {
            $collection = $this->mongoService->getCollection('articles');

            // Filtres optionnels
            $filter = ['actif' => true];

            if ($request->query->has('categorie')) {
                $filter['categorieId'] = $request->query->get('categorie');
            }

            if ($request->query->has('collection')) {
                $filter['collectionId'] = $request->query->get('collection');
            }

            $articles = $collection->find($filter, [
                'sort' => ['createdAt' => -1]
            ])->toArray();

            return $this->json(array_values($articles));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get article by slug (public)
     */
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function getArticleBySlug(string $slug): JsonResponse
    {
        try {
            $collection = $this->mongoService->getCollection('articles');
            $article = $collection->findOne([
                'slug' => $slug,
                'actif' => true
            ]);

            if (!$article) {
                return $this->json(
                    ['error' => 'Article non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json($article);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all articles including inactive (admin)
     */
    #[Route('/admin/all', name: 'admin_list', methods: ['GET'])]
    public function getAllArticles(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('articles');
            $articles = $collection->find([], [
                'sort' => ['createdAt' => -1]
            ])->toArray();

            return $this->json(array_values($articles));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get article by ID (admin)
     */
    #[Route('/admin/{id}', name: 'admin_show', methods: ['GET'])]
    public function getArticleById(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('articles');
            $article = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if (!$article) {
                return $this->json(
                    ['error' => 'Article non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json($article);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create article (admin)
     */
    #[Route('/admin', name: 'admin_create', methods: ['POST'])]
    public function createArticle(Request $request): JsonResponse
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
            $collection = $this->mongoService->getCollection('articles');
            $existing = $collection->findOne(['slug' => $data['slug']]);

            if ($existing) {
                return $this->json(
                    ['error' => 'Un article avec ce slug existe déjà'],
                    Response::HTTP_CONFLICT
                );
            }

            // Préparer les données
            $articleData = [
                'nom' => $data['nom'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'prix' => floatval($data['prix'] ?? 0),
                'categorieId' => $data['categorieId'] ?? null,
                'collectionId' => $data['collectionId'] ?? null,
                'images' => $data['images'] ?? [],
                'caracteristiques' => $data['caracteristiques'] ?? [],
                'actif' => $data['actif'] ?? true,
                'vedette' => $data['vedette'] ?? false,
                'stock' => intval($data['stock'] ?? 0),
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $collection->insertOne($articleData);
            $articleData['_id'] = $result->getInsertedId();

            return $this->json($articleData, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update article (admin)
     */
    #[Route('/admin/{id}', name: 'admin_update', methods: ['PUT'])]
    public function updateArticle(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $collection = $this->mongoService->getCollection('articles');

            // Vérifier que l'article existe
            $article = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$article) {
                return $this->json(
                    ['error' => 'Article non trouvé'],
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
            if (isset($data['prix'])) $updateData['prix'] = floatval($data['prix']);
            if (isset($data['categorieId'])) $updateData['categorieId'] = $data['categorieId'];
            if (isset($data['collectionId'])) $updateData['collectionId'] = $data['collectionId'];
            if (isset($data['images'])) $updateData['images'] = $data['images'];
            if (isset($data['caracteristiques'])) $updateData['caracteristiques'] = $data['caracteristiques'];
            if (isset($data['actif'])) $updateData['actif'] = $data['actif'];
            if (isset($data['vedette'])) $updateData['vedette'] = $data['vedette'];
            if (isset($data['stock'])) $updateData['stock'] = intval($data['stock']);

            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => $updateData]
            );

            $updatedArticle = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            return $this->json($updatedArticle);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate article (admin)
     */
    #[Route('/admin/{id}/duplicate', name: 'admin_duplicate', methods: ['POST'])]
    public function duplicateArticle(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('articles');

            // Récupérer l'article original
            $original = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if (!$original) {
                return $this->json(
                    ['error' => 'Article non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            // Créer une copie
            $duplicate = (array) $original;
            unset($duplicate['_id']);
            $duplicate['nom'] = $duplicate['nom'] . ' (Copie)';
            $duplicate['slug'] = $duplicate['slug'] . '-copie-' . time();
            $duplicate['actif'] = false;
            $duplicate['createdAt'] = new \MongoDB\BSON\UTCDateTime();
            $duplicate['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

            $result = $collection->insertOne($duplicate);
            $duplicate['_id'] = $result->getInsertedId();

            return $this->json($duplicate, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete article (admin)
     */
    #[Route('/admin/{id}', name: 'admin_delete', methods: ['DELETE'])]
    public function deleteArticle(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('articles');

            $result = $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if ($result->getDeletedCount() === 0) {
                return $this->json(
                    ['error' => 'Article non trouvé'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json(['message' => 'Article supprimé avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
