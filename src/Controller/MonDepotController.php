<?php

namespace App\Controller;

use App\Entity\DepotVente;
use App\Entity\DepotVenteStockItem;
use App\Entity\DepotVenteTransaction;
use App\Entity\DepotVenteTransactionLigne;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\DepotVenteRepository;
use App\Repository\DepotVenteStockItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-depot', name: 'mon_depot')]
#[IsGranted('ROLE_DEPOT_VENTE')]
class MonDepotController extends AbstractController
{
    public function __construct(
        private DepotVenteRepository $depotRepo,
        private DepotVenteStockItemRepository $stockRepo,
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private CategoryRepository $categoryRepo,
    ) {}

    private function getDepotOrRedirect(): DepotVente|Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $depot = $this->depotRepo->findOneBy(['user' => $user, 'actif' => true]);

        if (!$depot) {
            $this->addFlash('error', "Aucun dépôt-vente actif n'est associé à votre compte.");
            return $this->redirectToRoute('home');
        }

        return $depot;
    }

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $result = $this->getDepotOrRedirect();
        if ($result instanceof Response) return $result;
        $depot = $result;

        $articles = $this->articleRepo->findBy(['actif' => true], ['nom' => 'ASC']);

        $stockMap = [];
        foreach ($depot->getStockItems() as $item) {
            $stockMap[$item->getVariante()->getId()] = $item;
        }

        $prixMap = [];
        foreach ($articles as $article) {
            foreach ($article->getVariantes() as $variante) {
                $prixMap[$variante->getId()] = $this->resolveUnitPrice($article, $variante, 1);
            }
        }

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

        $articlesAvecStockItems = [];
        foreach ($articles as $article) {
            foreach ($article->getVariantes() as $variante) {
                if (isset($stockMap[$variante->getId()])) {
                    $articlesAvecStockItems[] = $article;
                    break;
                }
            }
        }

        $categories = $this->categoryRepo->findBy(['actif' => true], ['ordre' => 'ASC', 'nom' => 'ASC']);

        return $this->render('mon_depot/index.html.twig', [
            'depot'                  => $depot,
            'articles'               => $articlesAvecStock,
            'articlesAvecStockItems' => $articlesAvecStockItems,
            'categories'             => $categories,
            'stockMap'               => $stockMap,
            'prixMap'                => $prixMap,
            'transactions'           => $depot->getTransactions()->slice(0, 30),
        ]);
    }

    #[Route('/ajout', name: '_ajout', methods: ['POST'])]
    public function ajout(Request $request): Response
    {
        $result = $this->getDepotOrRedirect();
        if ($result instanceof Response) return $result;
        $depot = $result;

        $articleIds = $request->request->all('articles');
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

        return $this->redirectToRoute('mon_depot');
    }

    #[Route('/modifier-stock', name: '_modifier_stock', methods: ['POST'])]
    public function modifierStock(Request $request): Response
    {
        $result = $this->getDepotOrRedirect();
        if ($result instanceof Response) return $result;
        $depot = $result;

        $lignesData = $request->request->all('lignes');
        $note = trim($request->request->get('note', ''));

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $transaction = (new DepotVenteTransaction())
            ->setDepotVente($depot)
            ->setType(DepotVenteTransaction::TYPE_REASSORT)
            ->setNote($note ?: null)
            ->setCreatedBy($user);

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

        return $this->redirectToRoute('mon_depot');
    }

    #[Route('/supprimer-article/{articleId}', name: '_supprimer_article', methods: ['POST'])]
    public function supprimerArticle(int $articleId): Response
    {
        $result = $this->getDepotOrRedirect();
        if ($result instanceof Response) return $result;
        $depot = $result;

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

        return $this->redirectToRoute('mon_depot');
    }

    #[Route('/vente', name: '_vente', methods: ['POST'])]
    public function vente(Request $request): Response
    {
        $result = $this->getDepotOrRedirect();
        if ($result instanceof Response) return $result;
        $depot = $result;

        $lignesData = $request->request->all('lignes');
        $note = trim($request->request->get('note', ''));

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $transaction = (new DepotVenteTransaction())
            ->setDepotVente($depot)
            ->setType(DepotVenteTransaction::TYPE_VENTE)
            ->setNote($note ?: null)
            ->setCreatedBy($user);

        $totalReel = 0.0;
        $hasLines  = false;

        foreach ($lignesData as $stockItemId => $data) {
            $qty = (int)($data['qty'] ?? 0);
            if ($qty <= 0) continue;

            $stockItem = $this->stockRepo->find((int)$stockItemId);
            if (!$stockItem || $stockItem->getDepotVente() !== $depot) continue;

            if ($stockItem->getQuantite() < $qty) {
                $this->addFlash('error', 'Stock insuffisant pour « ' . $stockItem->getVariante()->getNom() . ' ».');
                continue;
            }

            $prixEstime = isset($data['prixEstime']) && $data['prixEstime'] !== '' ? (float)$data['prixEstime'] : null;
            $prixReel   = isset($data['prixReel'])   && $data['prixReel'] !== ''   ? (float)$data['prixReel']   : null;

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

        if ($hasLines) {
            $depot->setFondDeCaisse($depot->getFondDeCaisse() + $totalReel);
            $transaction->setMontantFond($totalReel);
            $this->em->persist($transaction);
            $this->em->flush();
            $this->addFlash('success', sprintf('Vente enregistrée. +%.2f € au fond de caisse.', $totalReel));
        } else {
            $this->addFlash('error', 'Aucune ligne valide dans la vente.');
        }

        return $this->redirectToRoute('mon_depot');
    }

    #[Route('/fond', name: '_fond', methods: ['POST'])]
    public function fond(Request $request): Response
    {
        $result = $this->getDepotOrRedirect();
        if ($result instanceof Response) return $result;
        $depot = $result;

        $sens    = $request->request->get('sens');
        $montant = abs((float)$request->request->get('montant', 0));
        $note    = trim($request->request->get('note', ''));

        if ($montant <= 0) {
            $this->addFlash('error', 'Montant invalide.');
            return $this->redirectToRoute('mon_depot');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

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
            ->setCreatedBy($user);

        $this->em->persist($transaction);
        $this->em->flush();

        $label = $sens === 'retrait' ? 'retiré' : 'ajouté';
        $this->addFlash('success', sprintf('%.2f € %s au fond de caisse.', $montant, $label));

        return $this->redirectToRoute('mon_depot');
    }

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
