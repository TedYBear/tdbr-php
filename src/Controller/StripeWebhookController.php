<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private CommandeRepository $commandeRepo,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            /** @var \Stripe\PaymentIntent $intent */
            $intent = $event->data->object;

            $commande = $this->commandeRepo->findOneBy([
                'stripePaymentIntentId' => $intent->id,
            ]);

            if ($commande && $commande->getStatut() !== 'payee') {
                $commande->setStatut('payee');
                $commande->setUpdatedAt(new \DateTimeImmutable());
                $this->em->flush();
            }
        }

        return new Response('OK', 200);
    }
}
