<?php

namespace App\Controller;

use App\Entity\DepotVente;
use App\Entity\DepotVenteTransaction;
use App\Entity\DepotVenteTransactionLigne;
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
    ) {}

    private function getDepotOrRedirect(): DepotVente|Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $depot = $this->depotRepo->findOneBy(['user' => $user, 'actif' => true]);

        if (!$depot) {
            $this->addFlash('error', 'Aucun dépôt-vente actif n'est associé à votre compte.');
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

        $stockItems = array_values(array_filter(
            $depot->getStockItems()->toArray(),
            fn($item) => $item->getQuantite() > 0
        ));

        return $this->render('mon_depot/index.html.twig', [
            'depot'        => $depot,
            'stockItems'   => $stockItems,
            'transactions' => $depot->getTransactions()->slice(0, 20),
        ]);
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

            $prixReel = isset($data['prixReel']) && $data['prixReel'] !== '' ? (float)$data['prixReel'] : null;

            $stockItem->addQuantite(-$qty);

            $label = $stockItem->getVariante()->getArticle()->getNom() . ' — ' . $stockItem->getVariante()->getNom();
            $ligne = (new DepotVenteTransactionLigne())
                ->setVariante($stockItem->getVariante())
                ->setVarianteLabel($label)
                ->setQuantite($qty)
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
}
