<?php

namespace App\Service;

use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct(
        private string $secretKey,
        private string $webhookSecret,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Crée un PaymentIntent pour un montant en euros.
     * @param float $amount Montant total en euros (ex : 29.99)
     */
    public function createPaymentIntent(float $amount, string $orderRef): PaymentIntent
    {
        return PaymentIntent::create([
            'amount'   => (int) round($amount * 100),
            'currency' => 'eur',
            'metadata' => ['order_ref' => $orderRef],
            'automatic_payment_methods' => ['enabled' => true],
        ]);
    }

    /**
     * Récupère un PaymentIntent via l'API Stripe (source of truth).
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Met à jour le montant d'un PaymentIntent existant.
     * @param float $amount Nouveau montant en euros
     */
    public function updatePaymentIntentAmount(string $paymentIntentId, float $amount): PaymentIntent
    {
        return PaymentIntent::update($paymentIntentId, [
            'amount' => (int) round($amount * 100),
        ]);
    }

    /**
     * Construit et vérifie un événement webhook Stripe depuis le payload brut + entête signature.
     * @throws SignatureVerificationException si la signature est invalide
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
    }
}
