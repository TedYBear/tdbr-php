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

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleAdminController extends AbstractController
{
    public function __construct(
        private MongoDBService $mongoService
    ) {
    }

    #[Route('', name: 'admin_articles')]
    public function index(Request $request): Response
    {
        $collection = $this->mongoService->getCollection('articles');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Filtres
        $search = $request->query->get('search');
        $filter = [];
        if ($search) {
            $filter['nom'] = new \MongoDB\Driver\Query\Regex($search, 'i');
        }

        $articles = $collection->find($filter, [
            'limit' => $limit,
            'skip' => $offset,
            'sort' => ['createdAt' => -1]
        ])->toArray();

        $total = $collection->countDocuments($filter);
        $totalPages = ceil($total / $limit);

        return $this->render('admin/articles/index.html.twig', [
            'articles' => $articles,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'admin_articles_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $collection = $this->mongoService->getCollection('articles');

            // Générer le slug
            $slug = $this->generateSlug($data['nom']);

            // Préparer les variantes
            $variantes = [];
            if (!empty($data['variantes'])) {
                foreach ($data['variantes'] as $index => $varianteData) {
                    if (!empty($varianteData['nom'])) {
                        $variantes[] = [
                            'id' => uniqid(),
                            'nom' => $varianteData['nom'],
                            'prix' => (float)($varianteData['prix'] ?? $data['prix']),
                            'stock' => (int)($varianteData['stock'] ?? 999)
                        ];
                    }
                }
            }

            $articleData = [
                'nom' => $data['nom'],
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'prix' => (float)$data['prix'],
                'stock' => (int)($data['stock'] ?? 999),
                'images' => $data['images'] ?? [],
                'categorieId' => !empty($data['categorieId']) ? new ObjectId($data['categorieId']) : null,
                'collectionId' => !empty($data['collectionId']) ? new ObjectId($data['collectionId']) : null,
                'variantes' => $variantes,
                'actif' => isset($data['actif']),
                'createdAt' => new UTCDateTime(),
                'updatedAt' => new UTCDateTime()
            ];

            $collection->insertOne($articleData);

            $this->addFlash('success', 'Article créé avec succès');
            return $this->redirectToRoute('admin_articles');
        }

        $categories = $this->mongoService->getCollection('categories')
            ->find(['actif' => true], ['sort' => ['nom' => 1]])
            ->toArray();

        $collections = $this->mongoService->getCollection('collections')
            ->find(['actif' => true], ['sort' => ['nom' => 1]])
            ->toArray();

        return $this->render('admin/articles/form.html.twig', [
            'article' => null,
            'categories' => $categories,
            'collections' => $collections
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_articles_edit')]
    public function edit(string $id, Request $request): Response
    {
        $collection = $this->mongoService->getCollection('articles');
        $article = $collection->findOne(['_id' => new ObjectId($id)]);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $slug = $this->generateSlug($data['nom']);

            // Préparer les variantes
            $variantes = [];
            if (!empty($data['variantes'])) {
                foreach ($data['variantes'] as $index => $varianteData) {
                    if (!empty($varianteData['nom'])) {
                        $variantes[] = [
                            'id' => $varianteData['id'] ?? uniqid(),
                            'nom' => $varianteData['nom'],
                            'prix' => (float)($varianteData['prix'] ?? $data['prix']),
                            'stock' => (int)($varianteData['stock'] ?? 999)
                        ];
                    }
                }
            }

            $updateData = [
                'nom' => $data['nom'],
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'prix' => (float)$data['prix'],
                'stock' => (int)($data['stock'] ?? 999),
                'images' => $data['images'] ?? [],
                'categorieId' => !empty($data['categorieId']) ? new ObjectId($data['categorieId']) : null,
                'collectionId' => !empty($data['collectionId']) ? new ObjectId($data['collectionId']) : null,
                'variantes' => $variantes,
                'actif' => isset($data['actif']),
                'updatedAt' => new UTCDateTime()
            ];

            $collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $updateData]
            );

            $this->addFlash('success', 'Article modifié avec succès');
            return $this->redirectToRoute('admin_articles');
        }

        $categories = $this->mongoService->getCollection('categories')
            ->find(['actif' => true], ['sort' => ['nom' => 1]])
            ->toArray();

        $collections = $this->mongoService->getCollection('collections')
            ->find(['actif' => true], ['sort' => ['nom' => 1]])
            ->toArray();

        return $this->render('admin/articles/form.html.twig', [
            'article' => $article,
            'categories' => $categories,
            'collections' => $collections
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_articles_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $collection = $this->mongoService->getCollection('articles');
        $collection->deleteOne(['_id' => new ObjectId($id)]);

        $this->addFlash('success', 'Article supprimé avec succès');
        return $this->redirectToRoute('admin_articles');
    }

    private function generateSlug(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
        return trim($text, '-');
    }
}
