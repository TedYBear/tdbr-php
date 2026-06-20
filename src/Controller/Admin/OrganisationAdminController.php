<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/organisation')]
#[IsGranted('ROLE_ADMIN')]
class OrganisationAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepo,
        private ProductCollectionRepository $collectionRepo,
        private ArticleRepository $articleRepo,
    ) {
    }

    #[Route('', name: 'admin_organisation')]
    public function index(): Response
    {
        $tree = [];
        foreach ($this->categoryRepo->findBy([], ['ordre' => 'ASC', 'nom' => 'ASC']) as $categorie) {
            $cols = [];
            foreach ($this->collectionRepo->findBy(['categorie' => $categorie], ['ordre' => 'ASC', 'nom' => 'ASC']) as $collection) {
                $cols[] = [
                    'collection' => $collection,
                    'articles'   => $this->articleRepo->findBy(['collection' => $collection], ['ordre' => 'ASC', 'nom' => 'ASC']),
                ];
            }
            $tree[] = ['categorie' => $categorie, 'collections' => $cols];
        }

        return $this->render('admin/organisation/index.html.twig', ['tree' => $tree]);
    }

    #[Route('/reorder/categories', name: 'admin_organisation_reorder_categories', methods: ['POST'])]
    public function reorderCategories(Request $request): Response
    {
        return $this->applyOrder($request, fn (int $id) => $this->categoryRepo->find($id));
    }

    #[Route('/reorder/collections', name: 'admin_organisation_reorder_collections', methods: ['POST'])]
    public function reorderCollections(Request $request): Response
    {
        return $this->applyOrder($request, fn (int $id) => $this->collectionRepo->find($id));
    }

    #[Route('/reorder/articles', name: 'admin_organisation_reorder_articles', methods: ['POST'])]
    public function reorderArticles(Request $request): Response
    {
        return $this->applyOrder($request, fn (int $id) => $this->articleRepo->find($id));
    }

    /**
     * Applique l'ordre reçu (liste d'IDs) en positionnant ordre = index.
     * Le client n'envoie que les frères d'un même parent → pas besoin de connaître le parent ici.
     */
    private function applyOrder(Request $request, callable $finder): Response
    {
        if (!$this->isCsrfTokenValid('app', (string) $request->headers->get('X-CSRF-Token', ''))) {
            return $this->json(['error' => 'Token de sécurité invalide, veuillez recharger la page'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $ids = is_array($data['ids'] ?? null) ? $data['ids'] : null;
        if ($ids === null) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $position = 0;
        foreach ($ids as $id) {
            $entity = $finder((int) $id);
            if ($entity !== null) {
                $entity->setOrdre($position++);
            }
        }
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
