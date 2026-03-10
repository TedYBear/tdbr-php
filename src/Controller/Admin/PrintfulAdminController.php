<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\ArticleImage;
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
    /** Delta de prix par taille (€) */
    private const TAILLE_DELTA = [
        '2XL' => 1.0,
        '3XL' => 2.0,
    ];

    public function __construct(
        private PrintfulService $printfulService,
        private CaracteristiqueRepository $caracteristiqueRepo,
        private FournisseurRepository $fournisseurRepo,
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

        // Fournisseur Printful : recherche automatique par nom
        $printfulFournisseur = $this->fournisseurRepo->createQueryBuilder('f')
            ->where('LOWER(f.nom) LIKE :name')
            ->setParameter('name', '%printful%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Valeurs connues Taille / Couleur
        [$tailleValues, $couleurValues] = $this->loadKnownValues();

        if ($request->isMethod('POST') && !$error) {
            $selectedIds = array_map('intval', (array)($request->request->all()['productIds'] ?? []));
            $grillePrix  = !empty($request->request->get('grillePrix'))
                ? $grillePrixRepo->find((int)$request->request->get('grillePrix')) : null;
            $collection  = !empty($request->request->get('collection'))
                ? $collectionRepo->find((int)$request->request->get('collection')) : null;
            $prixBase    = (float)($request->request->get('prixBase') ?? 0);
            $withMockups = (bool)$request->request->get('withMockups');

            $created  = 0;
            $updated  = 0;
            $unknowns = [];

            foreach ($products as $product) {
                if (!in_array((int)$product['id'], $selectedIds, true)) {
                    continue;
                }

                $slug            = $slugify->slugify($product['name']);
                $syncedVariants  = array_filter($product['variants'], fn($v) => $v['synced'] ?? false);
                $existingArticle = $articleRepo->findOneBy(['slug' => $slug]);

                if ($existingArticle) {
                    // Article existant : mettre à jour les variantes uniquement
                    $this->syncVariantesForArticle($existingArticle, $syncedVariants, $tailleValues, $couleurValues, $unknowns);
                    $existingArticle->setUpdatedAt(new \DateTimeImmutable());
                    $updated++;
                    continue;
                }

                $article = new Article();
                $article->setNom($product['name']);
                $article->setSlug($slug);
                $article->setPrixBase($prixBase);
                $article->setActif(false);
                $article->setCollection($collection);
                $article->setGrillePrix($grillePrix);
                $article->setFournisseur($printfulFournisseur);

                // Maquettes (nouvel article uniquement)
                if ($withMockups) {
                    foreach ($product['mockups'] as $idx => $url) {
                        $img = new ArticleImage();
                        $img->setUrl($url);
                        $img->setAlt($product['name']);
                        $img->setOrdre($idx);
                        $article->addImage($img);
                    }
                }

                $this->syncVariantesForArticle($article, $syncedVariants, $tailleValues, $couleurValues, $unknowns);

                $em->persist($article);
                $created++;
            }

            $em->flush();

            $parts = [];
            if ($created > 0) {
                $parts[] = "$created article(s) créé(s)";
            }
            if ($updated > 0) {
                $parts[] = "$updated article(s) mis à jour (variantes)";
            }
            if ($parts) {
                $this->addFlash('success', implode(', ', $parts) . '.');
            } else {
                $this->addFlash('warning', 'Aucun article importé — aucun produit sélectionné.');
            }

            if (!empty($unknowns)) {
                $this->addFlash('warning',
                    'Valeurs inconnues dans les Caractéristiques : ' . implode(', ', array_unique($unknowns)) . '.'
                );
            }

            return $this->redirectToRoute('admin_articles');
        }

        $productsWithParsing = $this->enrichProductsWithParsing($products, $tailleValues, $couleurValues);

        return $this->render('admin/printful/import.html.twig', [
            'products'           => $productsWithParsing,
            'error'              => $error,
            'collections'        => $collectionRepo->findBy(['actif' => true], ['nom' => 'ASC']),
            'grilles'            => $grillePrixRepo->findBy([], ['nom' => 'ASC']),
            'printfulFournisseur'=> $printfulFournisseur,
            'tailleValues'       => $tailleValues,
            'couleurValues'      => $couleurValues,
            'tailleDelta'        => self::TAILLE_DELTA,
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
            $couleur = $parts[count($parts) - 2];
            $taille  = $parts[count($parts) - 1];
        } elseif (count($parts) === 2) {
            $couleur = $parts[0];
            $taille  = $parts[1];
        } else {
            $couleur = $parts[0];
            $taille  = null;
        }

        $label = $taille !== null ? ($couleur . ' / ' . $taille) : $couleur;

        return ['label' => $label, 'couleur' => $couleur, 'taille' => $taille];
    }

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
     * Synchronise les variantes d'un article avec une liste de sync_variants Printful.
     * - Variante déjà présente (par printfulVariantId ou par nom) → mise à jour
     * - Variante absente → création
     * Les variantes existantes non présentes dans Printful sont laissées intactes.
     */
    private function syncVariantesForArticle(
        Article $article,
        array $syncedVariants,
        ?array $tailleValues,
        ?array $couleurValues,
        array &$unknowns,
    ): void {
        // Index des variantes existantes : par printfulVariantId et par nom
        $byPfId = [];
        $byNom  = [];
        foreach ($article->getVariantes() as $existing) {
            if ($existing->getPrintfulVariantId()) {
                $byPfId[$existing->getPrintfulVariantId()] = $existing;
            }
            $byNom[$existing->getNom()] = $existing;
        }

        foreach ($syncedVariants as $v) {
            $parsed  = $this->parseVariantName($v['name']);
            $couleur = $parsed['couleur'];
            $taille  = $parsed['taille'];
            $pfId    = (int)$v['id'];

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
            $delta = $taille !== null
                ? (self::TAILLE_DELTA[strtoupper(trim($taille))] ?? null)
                : null;

            // Trouver une variante existante à mettre à jour
            $variante = $byPfId[$pfId] ?? $byNom[$parsed['label']] ?? null;

            if ($variante) {
                $variante->setNom($parsed['label']);
                $variante->setPrintfulVariantId($pfId);
                $variante->setValeurs($valeurs);
                $variante->setDeltaPrix($delta);
                $variante->setSku($v['sku'] ?: null);
            } else {
                $variante = new Variante();
                $variante->setNom($parsed['label']);
                $variante->setPrintfulVariantId($pfId);
                $variante->setValeurs($valeurs);
                $variante->setDeltaPrix($delta);
                $variante->setSku($v['sku'] ?: null);
                $variante->setActif(true);
                $article->addVariante($variante);
            }
        }
    }

    private function enrichProductsWithParsing(array $products, ?array $tailleValues, ?array $couleurValues): array
    {
        return array_map(function (array $product) use ($tailleValues, $couleurValues) {
            $product['variants'] = array_map(function (array $v) use ($tailleValues, $couleurValues) {
                $parsed    = $this->parseVariantName($v['name']);
                $couleur   = $parsed['couleur'];
                $taille    = $parsed['taille'];
                $couleurOk = !$couleurValues || in_array($couleur, $couleurValues, true);
                $tailleOk  = !$tailleValues  || $taille === null || in_array($taille, $tailleValues, true);
                $delta     = $taille !== null ? (self::TAILLE_DELTA[strtoupper(trim($taille))] ?? null) : null;

                return $v + [
                    'parsed'    => $parsed,
                    'couleurOk' => $couleurOk,
                    'tailleOk'  => $tailleOk,
                    'valid'     => $couleurOk && $tailleOk,
                    'delta'     => $delta,
                ];
            }, $product['variants']);

            return $product;
        }, $products);
    }
}
