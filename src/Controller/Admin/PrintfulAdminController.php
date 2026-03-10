<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Variante;
use App\Repository\ArticleRepository;
use App\Repository\FournisseurRepository;
use App\Repository\GrillePrixRepository;
use App\Repository\ProductCollectionRepository;
use App\Service\PrintfulService;
use App\Service\SlugifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/printful')]
#[IsGranted('ROLE_ADMIN')]
class PrintfulAdminController extends AbstractController
{
    public function __construct(
        private PrintfulService $printfulService,
    ) {}

    #[Route('/sync-variants', name: 'admin_printful_sync_variants')]
    public function syncVariants(): Response
    {
        $products = null;
        $error    = null;

        try {
            $products = $this->printfulService->getSyncProducts();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return $this->render('admin/printful/sync_variants.html.twig', [
            'products' => $products,
            'error'    => $error,
        ]);
    }

    #[Route('/import', name: 'admin_printful_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        ProductCollectionRepository $collectionRepo,
        GrillePrixRepository $grillePrixRepo,
        FournisseurRepository $fournisseurRepo,
        ArticleRepository $articleRepo,
        EntityManagerInterface $em,
        SlugifyService $slugify,
    ): Response {
        $error    = null;
        $products = [];

        try {
            $products = $this->printfulService->getSyncProducts();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($request->isMethod('POST') && !$error) {
            $selectedIds = array_map('intval', (array)($request->request->all()['productIds'] ?? []));
            $grillePrix  = !empty($request->request->get('grillePrix'))
                ? $grillePrixRepo->find((int)$request->request->get('grillePrix')) : null;
            $collection  = !empty($request->request->get('collection'))
                ? $collectionRepo->find((int)$request->request->get('collection')) : null;
            $fournisseur = !empty($request->request->get('fournisseur'))
                ? $fournisseurRepo->find((int)$request->request->get('fournisseur')) : null;
            $prixBase    = (float)($request->request->get('prixBase') ?? 0);

            $created = 0;
            $skipped = 0;

            foreach ($products as $product) {
                if (!in_array((int)$product['id'], $selectedIds, true)) {
                    continue;
                }

                $slug = $slugify->slugify($product['name']);

                if ($articleRepo->findOneBy(['slug' => $slug])) {
                    $skipped++;
                    continue;
                }

                $article = new Article();
                $article->setNom($product['name']);
                $article->setSlug($slug);
                $article->setPrixBase($prixBase);
                $article->setActif(false);
                $article->setCollection($collection);
                $article->setGrillePrix($grillePrix);
                $article->setFournisseur($fournisseur);

                foreach ($product['variants'] as $v) {
                    if (!($v['synced'] ?? false)) {
                        continue;
                    }
                    $variante = new Variante();
                    $variante->setNom($this->parseVariantLabel($v['name'], $product['name']));
                    $variante->setPrintfulVariantId((int)$v['id']);
                    $variante->setValeurs($this->parseVariantAttributes($v['name'], $product['name']));
                    $variante->setSku($v['sku'] ?: null);
                    $variante->setActif(true);
                    $article->addVariante($variante);
                }

                $em->persist($article);
                $created++;
            }

            $em->flush();

            if ($created > 0) {
                $msg = "$created article(s) importé(s) avec succès.";
                if ($skipped > 0) {
                    $msg .= " $skipped ignoré(s) (slug déjà existant).";
                }
                $this->addFlash('success', $msg);
            } elseif ($skipped > 0) {
                $this->addFlash('warning', "Tous les articles sélectionnés existent déjà ($skipped ignoré(s)).");
            } else {
                $this->addFlash('warning', 'Aucun article importé — aucun produit sélectionné.');
            }

            return $this->redirectToRoute('admin_articles');
        }

        return $this->render('admin/printful/import.html.twig', [
            'products'     => $products,
            'error'        => $error,
            'collections'  => $collectionRepo->findBy(['actif' => true], ['nom' => 'ASC']),
            'grilles'      => $grillePrixRepo->findBy([], ['nom' => 'ASC']),
            'fournisseurs' => $fournisseurRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    private function parseVariantLabel(string $variantName, string $productName): string
    {
        $prefix = $productName . ' - ';
        if (str_starts_with($variantName, $prefix)) {
            return substr($variantName, strlen($prefix));
        }
        return $variantName;
    }

    private function parseVariantAttributes(string $variantName, string $productName): array
    {
        $part = $this->parseVariantLabel($variantName, $productName);
        if (str_contains($part, ' / ')) {
            [$couleur, $taille] = explode(' / ', $part, 2);
            return ['Couleur' => trim($couleur), 'Taille' => trim($taille)];
        }
        return ['Couleur' => trim($part)];
    }
}
