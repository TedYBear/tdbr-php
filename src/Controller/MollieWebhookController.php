<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Repository\PropositionCommercialeRepository;
use App\Service\MollieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MollieWebhookController extends AbstractController
{
    public function __construct(
        private MollieService $mollieService,
        private CommandeRepository $commandeRepo,
        private PropositionCommercialeRepository $propositionRepo,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Mollie envoie un POST avec le corps : id=tr_xxx
     * On récupère le paiement depuis l'API pour vérifier le statut.
     */
    #[Route('/webhook/mollie', name: 'mollie_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $mollieId = $request->request->get('id', '');

        if (!$mollieId) {
            return new Response('Missing id', 400);
        }

        try {
            $payment = $this->mollieService->getPayment($mollieId);
        } catch (\Exception $e) {
            return new Response('Payment not found', 200); // 200 pour éviter les retry Mollie
        }

        if (!$payment->isPaid()) {
            return new Response('OK', 200);
        }

        // Chercher la commande par molliePaymentId
        $commande = $this->commandeRepo->findOneBy(['molliePaymentId' => $mollieId]);

        if ($commande && $commande->getStatut() !== 'payee') {
            $commande->setStatut('payee');
            $commande->setUpdatedAt(new \DateTimeImmutable());

            // Mettre à jour la proposition associée si présente
            $proposition = $this->propositionRepo->findOneBy(['commande' => $commande]);
            if ($proposition && $proposition->getStatut() !== 'payee') {
                $proposition->setStatut('payee');
                $proposition->setUpdatedAt(new \DateTimeImmutable());
            }

            $this->em->flush();
        }

        return new Response('OK', 200);
    }
}
