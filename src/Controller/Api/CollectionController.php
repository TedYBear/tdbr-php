<?php

namespace App\Controller\Api;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/collections', name: 'api_collection_')]
class CollectionController extends AbstractController
{
    private MongoDBService $mongoService;

    public function __construct(MongoDBService $mongoService)
    {
        $this->mongoService = $mongoService;
    }

    /**
     * Get all active collections (public)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function getCollections(): JsonResponse
    {
        try {
            $collection = $this->mongoService->getCollection('collections');
            $collections = $collection->find(
                ['actif' => true],
                ['sort' => ['ordre' => 1]]
            )->toArray();

            return $this->json(array_values($collections));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get collection by slug (public)
     */
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function getCollectionBySlug(string $slug): JsonResponse
    {
        try {
            $collection = $this->mongoService->getCollection('collections');
            $collectionDoc = $collection->findOne([
                'slug' => $slug,
                'actif' => true
            ]);

            if (!$collectionDoc) {
                return $this->json(
                    ['error' => 'Collection non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json($collectionDoc);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all collections including inactive (admin)
     */
    #[Route('/admin/all', name: 'admin_list', methods: ['GET'])]
    public function getAllCollections(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('collections');
            $collections = $collection->find(
                [],
                ['sort' => ['ordre' => 1]]
            )->toArray();

            return $this->json(array_values($collections));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get collection by ID (admin)
     */
    #[Route('/admin/{id}', name: 'admin_show', methods: ['GET'])]
    public function getCollectionById(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('collections');
            $collectionDoc = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if (!$collectionDoc) {
                return $this->json(
                    ['error' => 'Collection non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json($collectionDoc);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create collection (admin)
     */
    #[Route('/admin', name: 'admin_create', methods: ['POST'])]
    public function createCollection(Request $request): JsonResponse
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
            $collection = $this->mongoService->getCollection('collections');
            $existing = $collection->findOne(['slug' => $data['slug']]);

            if ($existing) {
                return $this->json(
                    ['error' => 'Une collection avec ce slug existe déjà'],
                    Response::HTTP_CONFLICT
                );
            }

            // Préparer les données
            $collectionData = [
                'nom' => $data['nom'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? '',
                'image' => $data['image'] ?? '',
                'actif' => $data['actif'] ?? true,
                'ordre' => $data['ordre'] ?? 0,
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $collection->insertOne($collectionData);
            $collectionData['_id'] = $result->getInsertedId();

            return $this->json($collectionData, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update collection (admin)
     */
    #[Route('/admin/{id}', name: 'admin_update', methods: ['PUT'])]
    public function updateCollection(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $collection = $this->mongoService->getCollection('collections');

            // Vérifier que la collection existe
            $collectionDoc = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$collectionDoc) {
                return $this->json(
                    ['error' => 'Collection non trouvée'],
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
            if (isset($data['image'])) $updateData['image'] = $data['image'];
            if (isset($data['actif'])) $updateData['actif'] = $data['actif'];
            if (isset($data['ordre'])) $updateData['ordre'] = $data['ordre'];

            $collection->updateOne(
                ['_id' => new \MongoDB\BSON\ObjectId($id)],
                ['$set' => $updateData]
            );

            $updatedCollection = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            return $this->json($updatedCollection);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete collection (admin)
     */
    #[Route('/admin/{id}', name: 'admin_delete', methods: ['DELETE'])]
    public function deleteCollection(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $collection = $this->mongoService->getCollection('collections');

            $result = $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if ($result->getDeletedCount() === 0) {
                return $this->json(
                    ['error' => 'Collection non trouvée'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->json(['message' => 'Collection supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
