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

#[Route('/admin/collections')]
#[IsGranted('ROLE_ADMIN')]
class CollectionAdminController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    #[Route('', name: 'admin_collections')]
    public function index(): Response
    {
        $collections = $this->mongoService->getCollection('collections')
            ->find([], ['sort' => ['nom' => 1]])
            ->toArray();

        return $this->render('admin/collections/index.html.twig', [
            'collections' => $collections
        ]);
    }

    #[Route('/new', name: 'admin_collections_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $collection = $this->mongoService->getCollection('collections');

            $slug = $this->generateSlug($data['nom']);

            $collection->insertOne([
                'nom' => $data['nom'],
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'actif' => isset($data['actif']),
                'createdAt' => new UTCDateTime(),
                'updatedAt' => new UTCDateTime()
            ]);

            $this->addFlash('success', 'Collection créée avec succès');
            return $this->redirectToRoute('admin_collections');
        }

        return $this->render('admin/collections/form.html.twig', [
            'collection' => null
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_collections_edit')]
    public function edit(string $id, Request $request): Response
    {
        $collection = $this->mongoService->getCollection('collections');
        $collectionData = $collection->findOne(['_id' => new ObjectId($id)]);

        if (!$collectionData) {
            throw $this->createNotFoundException('Collection introuvable');
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
                    'actif' => isset($data['actif']),
                    'updatedAt' => new UTCDateTime()
                ]]
            );

            $this->addFlash('success', 'Collection modifiée avec succès');
            return $this->redirectToRoute('admin_collections');
        }

        return $this->render('admin/collections/form.html.twig', [
            'collection' => $collectionData
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_collections_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $this->mongoService->getCollection('collections')->deleteOne(['_id' => new ObjectId($id)]);
        $this->addFlash('success', 'Collection supprimée avec succès');
        return $this->redirectToRoute('admin_collections');
    }

    private function generateSlug(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
        return trim($text, '-');
    }
}
