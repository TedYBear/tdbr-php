<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Variante;
use App\Repository\ArticleRepository;
use App\Repository\CaracteristiqueRepository;
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
        private CaracteristiqueRepository $caracteristiqueRepo,
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

        // Charger les valeurs connues Taille et Couleur depuis les Caracteristiques
        [$tailleValues, $couleurValues] = $this->loadKnownValues();

        if ($request->isMethod('POST') && !$error) {
            $selectedIds = array_map('intval', (array)($request->request->all()['productIds'] ?? []));
            $grillePrix  = !empty($request->request->get('grillePrix'))
                ? $grillePrixRepo->find((int)$request->request->get('grillePrix')) : null;
            $collection  = !empty($request->request->get('collection'))
                ? $collectionRepo->find((int)$request->request->get('collection')) : null;
            $fournisseur = !empty($request->request->get('fournisseur'))
                ? $fournisseurRepo->find((int)$request->request->get('fournisseur')) : null;
            $prixBase    = (float)($request->request->get('prixBase') ?? 0);

            $created  = 0;
            $skipped  = 0;
            $unknowns = [];

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

                    $parsed   = $this->parseVariantName($v['name']);
                    $couleur  = $parsed['couleur'];
                    $taille   = $parsed['taille'];

                    // Collecter les valeurs inconnues pour avertissement
                    if ($couleurValues && !in_array($couleur, $couleurValues, true)) {
                        $unknowns[] = "Couleur «$couleur»";
                    }
                    if ($tailleValues && $taille && !in_array($taille, $tailleValues, true)) {
                        $unknowns[] = "Taille «$taille»";
                    }

                    $valeurs = ['Couleur' => $couleur];
                    if ($taille !== null) {
                        $valeurs['Taille'] = $taille;
                    }

                    $variante = new Variante();
                    $variante->setNom($parsed['label']);
                    $variante->setPrintfulVariantId((int)$v['id']);
                    $variante->setValeurs($valeurs);
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

            if (!empty($unknowns)) {
                $unique = array_unique($unknowns);
                $this->addFlash('warning',
                    'Valeurs inconnues dans les Caractéristiques : ' . implode(', ', $unique) . '. Pensez à les ajouter si nécessaire.'
                );
            }

            return $this->redirectToRoute('admin_articles');
        }

        // Enrichir les produits avec le parsing des variantes pour la prévisualisation
        $productsWithParsing = $this->enrichProductsWithParsing($products, $tailleValues, $couleurValues);

        return $this->render('admin/printful/import.html.twig', [
            'products'      => $productsWithParsing,
            'error'         => $error,
            'collections'   => $collectionRepo->findBy(['actif' => true], ['nom' => 'ASC']),
            'grilles'       => $grillePrixRepo->findBy([], ['nom' => 'ASC']),
            'fournisseurs'  => $fournisseurRepo->findBy([], ['nom' => 'ASC']),
            'tailleValues'  => $tailleValues,
            'couleurValues' => $couleurValues,
        ]);
    }

    /**
     * Parse un nom de variante Printful au format "titre/couleur/taille"
     * Retourne ['label' => 'Blanc / S', 'couleur' => 'Blanc', 'taille' => 'S']
     */
    private function parseVariantName(string $variantName): array
    {
        $parts = array_map('trim', explode('/', $variantName));

        if (count($parts) >= 3) {
            // titre/couleur/taille
            $couleur = $parts[count($parts) - 2];
            $taille  = $parts[count($parts) - 1];
            $label   = $couleur . ' / ' . $taille;
        } elseif (count($parts) === 2) {
            // couleur/taille
            $couleur = $parts[0];
            $taille  = $parts[1];
            $label   = $couleur . ' / ' . $taille;
        } else {
            // nom brut
            $couleur = $parts[0];
            $taille  = null;
            $label   = $couleur;
        }

        return ['label' => $label, 'couleur' => $couleur, 'taille' => $taille];
    }

    /**
     * Charge les valeurs connues pour les caractéristiques Taille et Couleur.
     * Retourne [$tailleValues, $couleurValues] (tableaux de strings, ou null si la carac n'existe pas).
     */
    private function loadKnownValues(): array
    {
        $tailleValues  = null;
        $couleurValues = null;

        foreach ($this->caracteristiqueRepo->findAll() as $carac) {
            $nom = mb_strtolower(trim($carac->getNom()));
            if ($nom === 'taille') {
                $tailleValues = $carac->getValeursArray();
            } elseif ($nom === 'couleur') {
                $couleurValues = $carac->getValeursArray();
            }
        }

        return [$tailleValues, $couleurValues];
    }

    /**
     * Enrichit chaque produit/variante avec les données de parsing pour la prévisualisation.
     */
    private function enrichProductsWithParsing(array $products, ?array $tailleValues, ?array $couleurValues): array
    {
        return array_map(function (array $product) use ($tailleValues, $couleurValues) {
            $product['variants'] = array_map(function (array $v) use ($product, $tailleValues, $couleurValues) {
                $parsed  = $this->parseVariantName($v['name']);
                $couleur = $parsed['couleur'];
                $taille  = $parsed['taille'];

                $couleurOk = !$couleurValues || in_array($couleur, $couleurValues, true);
                $tailleOk  = !$tailleValues  || $taille === null || in_array($taille, $tailleValues, true);

                return $v + [
                    'parsed'    => $parsed,
                    'couleurOk' => $couleurOk,
                    'tailleOk'  => $tailleOk,
                    'valid'     => $couleurOk && $tailleOk,
                ];
            }, $product['variants']);

            return $product;
        }, $products);
    }
}
