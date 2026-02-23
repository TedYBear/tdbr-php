<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\ArticleImage;
use App\Entity\Variante;
use App\Repository\ArticleRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ProductCollectionRepository;
use App\Repository\VarianteTemplateRepository;
use App\Service\SlugifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private ProductCollectionRepository $collectionRepo,
        private FournisseurRepository $fournisseurRepo,
        private VarianteTemplateRepository $templateRepo,
        private SlugifyService $slugify,
    ) {
    }

    private function buildTemplatesData(): array
    {
        return array_map(function($t) {
            return [
                'id' => $t->getId(),
                'nom' => $t->getNom(),
                'description' => $t->getDescription(),
                'caracteristiques' => $t->getCaracteristiques()->map(fn($c) => [
                    'nom' => $c->getNom(),
                    'valeurs' => $c->getValeursArray(),
                ])->toArray(),
            ];
        }, $this->templateRepo->findAll());
    }

    private function saveVariantes(Article $article, array $data): void
    {
        foreach ($article->getVariantes()->toArray() as $v) {
            $article->removeVariante($v);
        }
        if (!empty($data['variantes'])) {
            foreach ($data['variantes'] as $varianteData) {
                if (!empty($varianteData['nom'])) {
                    $v = new Variante();
                    $v->setNom($varianteData['nom']);
                    $v->setSku($varianteData['sku'] ?? null);
                    $v->setPrix(!empty($varianteData['prix']) ? (float)$varianteData['prix'] : (float)($data['prix'] ?? 0));
                    $v->setActif(isset($varianteData['actif']));
                    $valeurs = !empty($varianteData['valeurs']) ? json_decode($varianteData['valeurs'], true) : null;
                    $v->setValeurs(is_array($valeurs) ? $valeurs : null);
                    $article->addVariante($v);
                }
            }
        }
    }

    #[Route('', name: 'admin_articles')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $search = $request->query->get('search');
        $collectionId = $request->query->get('collection') ? (int)$request->query->get('collection') : null;
        $fournisseurId = $request->query->get('fournisseur') ? (int)$request->query->get('fournisseur') : null;

        $qb = $this->articleRepo->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($search) {
            $qb->andWhere('a.nom LIKE :search')->setParameter('search', '%' . $search . '%');
        }
        if ($collectionId) {
            $qb->andWhere('a.collection = :collection')->setParameter('collection', $collectionId);
        }
        if ($fournisseurId) {
            $qb->andWhere('a.fournisseur = :fournisseur')->setParameter('fournisseur', $fournisseurId);
        }

        $articles = $qb->getQuery()->getResult();

        $countQb = $this->articleRepo->createQueryBuilder('a')->select('COUNT(a.id)');
        if ($search) {
            $countQb->andWhere('a.nom LIKE :search')->setParameter('search', '%' . $search . '%');
        }
        if ($collectionId) {
            $countQb->andWhere('a.collection = :collection')->setParameter('collection', $collectionId);
        }
        if ($fournisseurId) {
            $countQb->andWhere('a.fournisseur = :fournisseur')->setParameter('fournisseur', $fournisseurId);
        }
        $total = (int)$countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int)ceil($total / $limit);

        $collections = $this->collectionRepo->findBy([], ['nom' => 'ASC']);
        $fournisseurs = $this->fournisseurRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/articles/index.html.twig', [
            'articles' => $articles,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'collections' => $collections,
            'collectionId' => $collectionId,
            'fournisseurs' => $fournisseurs,
            'fournisseurId' => $fournisseurId,
        ]);
    }

    #[Route('/new', name: 'admin_articles_new')]
    public function new(Request $request): Response
    {
        $collections = $this->collectionRepo->findBy(['actif' => true], ['nom' => 'ASC']);
        $fournisseurs = $this->fournisseurRepo->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $article = new Article();
            $article->setNom($data['nom']);
            $article->setSlug($this->slugify->slugify($data['nom']));
            $article->setDescription($data['description'] ?? null);
            $article->setPrixBase((float)($data['prix'] ?? 0));
            $article->setActif(isset($data['actif']));
            $article->setEnVedette(isset($data['enVedette']));

            if (!empty($data['collection'])) {
                $col = $this->collectionRepo->find((int)$data['collection']);
                $article->setCollection($col);
            }

            $fournisseur = !empty($data['fournisseur']) ? $this->fournisseurRepo->find((int)$data['fournisseur']) : null;
            $article->setFournisseur($fournisseur);

            // Images
            if (!empty($data['images'])) {
                $rawImages = is_array($data['images']) ? implode("\n", $data['images']) : $data['images'];
                $ordre = 0;
                foreach (explode("\n", $rawImages) as $imageUrl) {
                    $imageUrl = trim($imageUrl);
                    if (!empty($imageUrl)) {
                        $img = new ArticleImage();
                        $img->setUrl($imageUrl);
                        $img->setAlt($data['nom']);
                        $img->setOrdre($ordre++);
                        $article->addImage($img);
                    }
                }
            }

            $this->saveVariantes($article, $data);
            $this->em->persist($article);
            $this->em->flush();

            $this->addFlash('success', 'Article créé avec succès');
            return $this->redirectToRoute('admin_articles');
        }

        return $this->render('admin/articles/form.html.twig', [
            'article' => null,
            'collections' => $collections,
            'fournisseurs' => $fournisseurs,
            'templates' => $this->buildTemplatesData(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_articles_edit')]
    public function edit(int $id, Request $request): Response
    {
        $article = $this->articleRepo->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable');
        }

        $collections = $this->collectionRepo->findBy(['actif' => true], ['nom' => 'ASC']);
        $fournisseurs = $this->fournisseurRepo->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $article->setNom($data['nom']);
            $article->setSlug($this->slugify->slugify($data['nom']));
            $article->setDescription($data['description'] ?? null);
            $article->setPrixBase((float)($data['prix'] ?? 0));
            $article->setActif(isset($data['actif']));
            $article->setEnVedette(isset($data['enVedette']));
            $article->setUpdatedAt(new \DateTimeImmutable());

            $col = !empty($data['collection']) ? $this->collectionRepo->find((int)$data['collection']) : null;
            $article->setCollection($col);

            $fournisseur = !empty($data['fournisseur']) ? $this->fournisseurRepo->find((int)$data['fournisseur']) : null;
            $article->setFournisseur($fournisseur);

            // Images : remplacer si nouvelles fournies
            if (!empty($data['images'])) {
                foreach ($article->getImages()->toArray() as $img) {
                    $article->removeImage($img);
                }
                $ordre = 0;
                foreach ((array)$data['images'] as $imageUrl) {
                    $imageUrl = trim($imageUrl);
                    if (!empty($imageUrl)) {
                        $img = new ArticleImage();
                        $img->setUrl($imageUrl);
                        $img->setAlt($data['nom']);
                        $img->setOrdre($ordre++);
                        $article->addImage($img);
                    }
                }
            }

            $this->saveVariantes($article, $data);
            $this->em->flush();

            $this->addFlash('success', 'Article modifié avec succès');
            return $this->redirectToRoute('admin_articles');
        }

        return $this->render('admin/articles/form.html.twig', [
            'article' => $article,
            'collections' => $collections,
            'fournisseurs' => $fournisseurs,
            'templates' => $this->buildTemplatesData(),
        ]);
    }

    #[Route('/{id}/set-fournisseur', name: 'admin_articles_set_fournisseur', methods: ['POST'])]
    public function setFournisseur(int $id, Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $article = $this->articleRepo->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $fournisseurId = $data['fournisseurId'] ?? null;

        $fournisseur = $fournisseurId ? $this->fournisseurRepo->find((int)$fournisseurId) : null;
        $article->setFournisseur($fournisseur);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'nom' => $fournisseur ? $fournisseur->getNom() : null,
        ]);
    }

    #[Route('/{id}/toggle/{field}', name: 'admin_articles_toggle', methods: ['POST'])]
    public function toggle(int $id, string $field): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $article = $this->articleRepo->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article introuvable'], 404);
        }

        if ($field === 'actif') {
            $article->setActif(!$article->isActif());
            $value = $article->isActif();
        } elseif ($field === 'enVedette') {
            $article->setEnVedette(!$article->isEnVedette());
            $value = $article->isEnVedette();
        } else {
            return $this->json(['error' => 'Champ invalide'], 400);
        }

        $this->em->flush();
        return $this->json(['success' => true, 'value' => $value]);
    }

    #[Route('/{id}/clone', name: 'admin_articles_clone', methods: ['POST'])]
    public function clone(int $id): Response
    {
        $source = $this->articleRepo->find($id);

        if (!$source) {
            throw $this->createNotFoundException('Article introuvable');
        }

        $clone = new Article();
        $clone->setNom($source->getNom() . ' (copie)');
        $clone->setSlug($this->slugify->slugify($source->getNom()) . '-copie-' . substr(uniqid(), -6));
        $clone->setDescription($source->getDescription());
        $clone->setPrixBase($source->getPrixBase());
        $clone->setActif(false);
        $clone->setEnVedette(false);
        $clone->setCollection($source->getCollection());
        $clone->setFournisseur($source->getFournisseur());

        foreach ($source->getImages() as $img) {
            $newImg = new ArticleImage();
            $newImg->setUrl($img->getUrl());
            $newImg->setAlt($img->getAlt());
            $newImg->setOrdre($img->getOrdre());
            $clone->addImage($newImg);
        }

        foreach ($source->getVariantes() as $v) {
            $newV = new Variante();
            $newV->setNom($v->getNom());
            $newV->setSku($v->getSku() ? $v->getSku() . '-C' : null);
            $newV->setPrix($v->getPrix());
            $newV->setValeurs($v->getValeurs());
            $newV->setActif(false);
            $clone->addVariante($newV);
        }

        $this->em->persist($clone);
        $this->em->flush();

        $this->addFlash('success', 'Article cloné avec succès');
        return $this->redirectToRoute('admin_articles_edit', ['id' => $clone->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_articles_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $article = $this->articleRepo->find($id);
        if ($article) {
            $this->em->remove($article);
            $this->em->flush();
        }

        $this->addFlash('success', 'Article supprimé avec succès');
        return $this->redirectToRoute('admin_articles');
    }
}
