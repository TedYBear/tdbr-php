<?php

namespace App\Service;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;

class MollieService
{
    private MollieApiClient $client;

    public function __construct(string $apiKey)
    {
        $this->client = new MollieApiClient();
        $this->client->setApiKey($apiKey);
    }

    /**
     * Crée un paiement Mollie et retourne l'objet Payment.
     * $amount en euros (ex: 29.99)
     */
    public function createPayment(
        float $amount,
        string $description,
        string $redirectUrl,
        string $webhookUrl,
        array $metadata = []
    ): Payment {
        return $this->client->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value'    => number_format($amount, 2, '.', ''),
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'webhookUrl'  => $webhookUrl,
            'metadata'    => $metadata,
        ]);
    }

    /**
     * Récupère un paiement Mollie par son ID (tr_xxx).
     */
    public function getPayment(string $id): Payment
    {
        return $this->client->payments->get($id);
    }
}
