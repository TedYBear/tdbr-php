<?php

namespace App\Controller\Admin;

use App\Entity\ProductCollection;
use App\Repository\CategoryRepository;
use App\Repository\ProductCollectionRepository;
use App\Service\SlugifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/collections')]
#[IsGranted('ROLE_ADMIN')]
class CollectionAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductCollectionRepository $collectionRepo,
        private CategoryRepository $categoryRepo,
        private SlugifyService $slugify,
    ) {
    }

    #[Route('', name: 'admin_collections')]
    public function index(): Response
    {
        $collections = $this->collectionRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/collections/index.html.twig', [
            'collections' => $collections
        ]);
    }

    #[Route('/new', name: 'admin_collections_new')]
    public function new(Request $request): Response
    {
        $categories = $this->categoryRepo->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $collection = new ProductCollection();
            $collection->setNom($data['nom']);
            $collection->setSlug($this->slugify->slugify($data['nom']));
            $collection->setDescription($data['description'] ?? null);
            $collection->setActif(isset($data['actif']));
            $collection->setOrdre((int)($data['ordre'] ?? 0));

            if (!empty($data['categorie'])) {
                $categorie = $this->categoryRepo->find((int)$data['categorie']);
                $collection->setCategorie($categorie);
            }

            $this->em->persist($collection);
            $this->em->flush();

            $this->addFlash('success', 'Collection créée avec succès');
            return $this->redirectToRoute('admin_collections');
        }

        return $this->render('admin/collections/form.html.twig', [
            'collection' => null,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_collections_edit')]
    public function edit(int $id, Request $request): Response
    {
        $collection = $this->collectionRepo->find($id);

        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable');
        }

        $categories = $this->categoryRepo->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $collection->setNom($data['nom']);
            $collection->setSlug($this->slugify->slugify($data['nom']));
            $collection->setDescription($data['description'] ?? null);
            $collection->setActif(isset($data['actif']));
            $collection->setOrdre((int)($data['ordre'] ?? 0));

            $categorie = !empty($data['categorie']) ? $this->categoryRepo->find((int)$data['categorie']) : null;
            $collection->setCategorie($categorie);

            $this->em->flush();

            $this->addFlash('success', 'Collection modifiée avec succès');
            return $this->redirectToRoute('admin_collections');
        }

        return $this->render('admin/collections/form.html.twig', [
            'collection' => $collection,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_collections_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $collection = $this->collectionRepo->find($id);
        if ($collection) {
            $this->em->remove($collection);
            $this->em->flush();
        }

        $this->addFlash('success', 'Collection supprimée avec succès');
        return $this->redirectToRoute('admin_collections');
    }
}
