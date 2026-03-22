<?php

namespace App\Controller\Admin;

use App\Entity\DepotVente;
use App\Entity\DepotVenteStockItem;
use App\Entity\DepotVenteTransaction;
use App\Entity\DepotVenteTransactionLigne;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\DepotVenteRepository;
use App\Repository\DepotVenteStockItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/depot-ventes', name: 'admin_depot_ventes')]
#[IsGranted('ROLE_ADMIN')]
class DepotVenteAdminController extends AbstractController
{
    public function __construct(
        private DepotVenteRepository $repo,
        private DepotVenteStockItemRepository $stockRepo,
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private CategoryRepository $categoryRepo,
        private UserRepository $userRepo,
    ) {}

    // ─── Liste ────────────────────────────────────────────────────────────────

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $depots = $this->repo->findBy([], ['nom' => 'ASC']);

        return $this->render('admin/depot_vente/index.html.twig', [
            'depots' => $depots,
        ]);
    }

    // ─── Création ─────────────────────────────────────────────────────────────

    #[Route('/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $depot = new DepotVente();
            $this->hydrateFromRequest($depot, $request);
            $this->em->persist($depot);
            $this->em->flush();

            $this->addFlash('success', 'Dépôt-vente créé.');
            return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
        }

        return $this->render('admin/depot_vente/form.html.twig', [
            'depot' => null,
            'users' => $this->userRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    // ─── Modification ─────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(DepotVente $depot, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->hydrateFromRequest($depot, $request);
            $this->em->flush();

            $this->addFlash('success', 'Dépôt-vente mis à jour.');
            return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
        }

        return $this->render('admin/depot_vente/form.html.twig', [
            'depot' => $depot,
            'users' => $this->userRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    // ─── Suppression ──────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(DepotVente $depot): Response
    {
        $nom = $depot->getNom();
        $this->em->remove($depot);
        $this->em->flush();

        $this->addFlash('success', "Dépôt-vente « {$nom} » supprimé.");
        return $this->redirectToRoute('admin_depot_ventes');
    }

    // ─── Fiche détail ─────────────────────────────────────────────────────────

    #[Route('/{id}', name: '_detail', methods: ['GET'])]
    public function detail(DepotVente $depot): Response
    {
        $articles = $this->articleRepo->findBy(['actif' => true], ['nom' => 'ASC']);

        // Map varianteId => stockItem
        $stockMap = [];
        foreach ($depot->getStockItems() as $item) {
            $stockMap[$item->getVariante()->getId()] = $item;
        }

        // Map varianteId => prix unitaire (palier 1)
        $prixMap = [];
        foreach ($articles as $article) {
            foreach ($article->getVariantes() as $variante) {
                $prixMap[$variante->getId()] = $this->resolveUnitPrice($article, $variante, 1);
            }
        }

        // Articles ayant ≥1 variante en stock (qty > 0) — vue consultation
        $articlesAvecStock = [];
        foreach ($articles as $article) {
            foreach ($article->getVariantes() as $variante) {
                $item = $stockMap[$variante->getId()] ?? null;
                if ($item && $item->getQuantite() > 0) {
                    $articlesAvecStock[] = $article;
                    break;
                }
            }
        }

        // Articles ayant ≥1 stock item (qté quelconque) — vue modification
        $articlesAvecStockItems = [];
        foreach ($articles as $article) {
            foreach ($article->getVariantes() as $variante) {
                if (isset($stockMap[$variante->getId()])) {
                    $articlesAvecStockItems[] = $article;
                    break;
                }
            }
        }

        // Catégories avec leurs collections (pour "Ajouter des articles")
        $categories = $this->categoryRepo->findBy(['actif' => true], ['ordre' => 'ASC', 'nom' => 'ASC']);

        return $this->render('admin/depot_vente/detail.html.twig', [
            'depot'                  => $depot,
            'articles'               => $articlesAvecStock,
            'articlesAvecStockItems' => $articlesAvecStockItems,
            'categories'             => $categories,
            'stockMap'               => $stockMap,
            'prixMap'                => $prixMap,
            'transactions'           => $depot->getTransactions()->slice(0, 30),
        ]);
    }

    // ─── Ajout d'articles au stock ────────────────────────────────────────────

    #[Route('/{id}/ajout', name: '_ajout', methods: ['POST'])]
    public function ajout(DepotVente $depot, Request $request): Response
    {
        $articleIds = $request->request->all('articles'); // [articleId, ...]

        $added = 0;
        foreach ($articleIds as $articleId) {
            $article = $this->articleRepo->find((int)$articleId);
            if (!$article) continue;

            foreach ($article->getVariantes() as $variante) {
                if (!$variante->isActif()) continue;

                $existing = $this->stockRepo->findOneByDepotAndVariante($depot, $variante);
                if ($existing) continue;

                $stockItem = (new DepotVenteStockItem())
                    ->setDepotVente($depot)
                    ->setVariante($variante)
                    ->setQuantite(0);
                $this->em->persist($stockItem);
                $added++;
            }
        }

        if ($added > 0) {
            $this->em->flush();
            $this->addFlash('success', $added . ' variante(s) ajoutée(s) au suivi.');
        } else {
            $this->addFlash('success', 'Aucune nouvelle variante à ajouter.');
        }

        return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
    }

    // ─── Réinitialisation complète ────────────────────────────────────────────

    #[Route('/{id}/reinit', name: '_reinit', methods: ['POST'])]
    public function reinit(DepotVente $depot): Response
    {
        foreach ($depot->getTransactions() as $transaction) {
            foreach ($transaction->getLignes() as $ligne) {
                $this->em->remove($ligne);
            }
            $this->em->remove($transaction);
        }

        foreach ($depot->getStockItems() as $stockItem) {
            $this->em->remove($stockItem);
        }

        $this->em->flush();

        $this->addFlash('success', 'Dépôt-vente réinitialisé.');
        return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
    }

    // ─── Suppression d'un article du suivi ───────────────────────────────────

    #[Route('/{id}/supprimer-article/{articleId}', name: '_supprimer_article', methods: ['POST'])]
    public function supprimerArticle(DepotVente $depot, int $articleId): Response
    {
        $article = $this->articleRepo->find($articleId);
        if ($article) {
            foreach ($article->getVariantes() as $variante) {
                $stockItem = $this->stockRepo->findOneByDepotAndVariante($depot, $variante);
                if ($stockItem) {
                    $this->em->remove($stockItem);
                }
            }
            $this->em->flush();
            $this->addFlash('success', '« ' . $article->getNom() . ' » retiré du suivi.');
        }

        return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
    }

    // ─── Modification des stocks ───────────────────────────────────────────────

    #[Route('/{id}/modifier-stock', name: '_modifier_stock', methods: ['POST'])]
    public function modifierStock(DepotVente $depot, Request $request): Response
    {
        $lignesData = $request->request->all('lignes'); // [stockItemId => qty]
        $note = trim($request->request->get('note', ''));

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $transaction = (new DepotVenteTransaction())
            ->setDepotVente($depot)
            ->setType(DepotVenteTransaction::TYPE_REASSORT)
            ->setNote($note ?: null)
            ->setCreatedBy($admin);

        $hasChanges = false;

        foreach ($lignesData as $stockItemId => $qty) {
            $qty = max(0, (int)$qty);
            $stockItem = $this->stockRepo->find((int)$stockItemId);
            if (!$stockItem || $stockItem->getDepotVente() !== $depot) continue;

            $ancien = $stockItem->getQuantite();
            if ($ancien === $qty) continue;

            $delta = $qty - $ancien;
            $stockItem->setQuantite($qty);

            $label = $stockItem->getVariante()->getArticle()->getNom() . ' — ' . $stockItem->getVariante()->getNom();
            $ligne = (new DepotVenteTransactionLigne())
                ->setVariante($stockItem->getVariante())
                ->setVarianteLabel($label)
                ->setQuantite($delta);
            $transaction->addLigne($ligne);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->em->persist($transaction);
            $this->em->flush();
            $this->addFlash('success', 'Stock mis à jour.');
        } else {
            $this->addFlash('success', 'Aucune modification.');
        }

        return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
    }

    // ─── Vente ────────────────────────────────────────────────────────────────

    #[Route('/{id}/vente', name: '_vente', methods: ['POST'])]
    public function vente(DepotVente $depot, Request $request): Response
    {
        $lignesData = $request->request->all('lignes'); // ['stockItemId' => ['qty' => x, 'prixReel' => y]]
        $note = trim($request->request->get('note', ''));

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $transaction = (new DepotVenteTransaction())
            ->setDepotVente($depot)
            ->setType(DepotVenteTransaction::TYPE_VENTE)
            ->setNote($note ?: null)
            ->setCreatedBy($admin);

        $totalReel = 0.0;
        $hasLines = false;
        $errors = [];

        foreach ($lignesData as $stockItemId => $data) {
            $qty = (int)($data['qty'] ?? 0);
            if ($qty <= 0) continue;

            $stockItem = $this->stockRepo->find((int)$stockItemId);
            if (!$stockItem || $stockItem->getDepotVente() !== $depot) continue;

            if ($stockItem->getQuantite() < $qty) {
                $errors[] = 'Stock insuffisant pour ' . $stockItem->getVariante()->getNom();
                continue;
            }

            $prixEstime = isset($data['prixEstime']) ? (float)$data['prixEstime'] : null;
            $prixReel   = isset($data['prixReel'])   ? (float)$data['prixReel']   : null;

            $stockItem->addQuantite(-$qty);

            $label = $stockItem->getVariante()->getArticle()->getNom() . ' — ' . $stockItem->getVariante()->getNom();
            $ligne = (new DepotVenteTransactionLigne())
                ->setVariante($stockItem->getVariante())
                ->setVarianteLabel($label)
                ->setQuantite($qty)
                ->setPrixEstime($prixEstime !== null ? $prixEstime * $qty : null)
                ->setPrixReel($prixReel !== null ? $prixReel * $qty : null);

            $transaction->addLigne($ligne);
            $totalReel += $ligne->getPrixReel() ?? 0.0;
            $hasLines = true;
        }

        if (!empty($errors)) {
            foreach ($errors as $err) {
                $this->addFlash('error', $err);
            }
        }

        if ($hasLines) {
            // Mettre à jour le fond de caisse
            $depot->setFondDeCaisse($depot->getFondDeCaisse() + $totalReel);
            $transaction->setMontantFond($totalReel);

            $this->em->persist($transaction);
            $this->em->flush();
            $this->addFlash('success', sprintf('Vente enregistrée. +%.2f € au fond de caisse.', $totalReel));
        } else {
            $this->addFlash('error', 'Aucune ligne valide dans la vente.');
        }

        return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
    }

    // ─── Fond de caisse ───────────────────────────────────────────────────────

    #[Route('/{id}/fond', name: '_fond', methods: ['POST'])]
    public function fond(DepotVente $depot, Request $request): Response
    {
        $sens    = $request->request->get('sens'); // 'ajout' | 'retrait'
        $montant = abs((float)$request->request->get('montant', 0));
        $note    = trim($request->request->get('note', ''));

        if ($montant <= 0) {
            $this->addFlash('error', 'Montant invalide.');
            return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
        }

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $delta = $sens === 'retrait' ? -$montant : $montant;
        $type  = $sens === 'retrait'
            ? DepotVenteTransaction::TYPE_FOND_RETRAIT
            : DepotVenteTransaction::TYPE_FOND_AJOUT;

        $depot->setFondDeCaisse($depot->getFondDeCaisse() + $delta);

        $transaction = (new DepotVenteTransaction())
            ->setDepotVente($depot)
            ->setType($type)
            ->setMontantFond($delta)
            ->setNote($note ?: null)
            ->setCreatedBy($admin);

        $this->em->persist($transaction);
        $this->em->flush();

        $label = $sens === 'retrait' ? 'retiré' : 'ajouté';
        $this->addFlash('success', sprintf('%.2f € %s au fond de caisse.', $montant, $label));

        return $this->redirectToRoute('admin_depot_ventes_detail', ['id' => $depot->getId()]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function hydrateFromRequest(DepotVente $depot, Request $request): void
    {
        $userId = $request->request->get('userId');
        $user = $userId ? $this->userRepo->find((int)$userId) : null;

        $depot
            ->setNom(trim($request->request->get('nom', '')))
            ->setAdresse(trim($request->request->get('adresse', '')) ?: null)
            ->setCodePostal(trim($request->request->get('codePostal', '')) ?: null)
            ->setVille(trim($request->request->get('ville', '')) ?: null)
            ->setTelephone(trim($request->request->get('telephone', '')) ?: null)
            ->setEmail(trim($request->request->get('email', '')) ?: null)
            ->setUser($user)
            ->setActif($request->request->has('actif'));
    }

    /** Calcule le prix unitaire estimé selon la grille de prix de l'article */
    private function resolveUnitPrice(\App\Entity\Article $article, \App\Entity\Variante $variante, int $qty = 1): ?float
    {
        $grille = $article->getGrillePrix();
        if (!$grille) {
            return $article->getPrixBase() + ($variante->getDeltaPrix() ?? 0.0);
        }

        $paliers = $grille->getPaliers();
        $resolved = null;
        foreach ($paliers as $palier) {
            if ($qty >= ($palier['min'] ?? 0) && isset($palier['prixVente']) && $palier['prixVente'] !== null) {
                $resolved = (float)$palier['prixVente'];
            }
        }

        if ($resolved === null) return null;
        return $resolved + ($variante->getDeltaPrix() ?? 0.0);
    }
}
