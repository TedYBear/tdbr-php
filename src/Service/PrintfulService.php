<?php

namespace App\Service;

use App\Entity\Commande;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PrintfulService
{
    private const API_BASE = 'https://api.printful.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $storeId,
    ) {}

    /**
     * Crée une commande en brouillon dans Printful.
     * Retourne l'ID Printful de la commande créée.
     *
     * @param array $items Items filtrés ayant un printfulVariantId non nul,
     *                     chacun avec les clés : printfulVariantId, quantity, prix
     */
    public function createDraftOrder(Commande $commande, array $items): int
    {
        $adresse = $commande->getAdresseLivraison();
        $client  = $commande->getClient();

        $recipient = [
            'name'         => trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')),
            'address1'     => $adresse['adresse'] ?? '',
            'city'         => $adresse['ville'] ?? '',
            'zip'          => $adresse['codePostal'] ?? '',
            'country_code' => $adresse['pays'] ?? 'FR',
        ];
        if (!empty($client['email'])) {
            $recipient['email'] = $client['email'];
        }
        if (!empty($client['telephone'])) {
            $recipient['phone'] = $client['telephone'];
        }
        if (!empty($adresse['complementAdresse'])) {
            $recipient['address2'] = $adresse['complementAdresse'];
        }

        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = [
                'sync_variant_id' => (int) $item['printfulVariantId'],
                'quantity'        => (int) $item['quantity'],
                'retail_price'    => number_format((float) $item['prix'], 2, '.', ''),
            ];
        }

        $response = $this->httpClient->request('POST', self::API_BASE . '/orders', [
            'headers' => [
                'Authorization'  => 'Bearer ' . $this->apiKey,
                'Content-Type'   => 'application/json',
                'X-PF-Store-Id'  => $this->storeId,
            ],
            'json' => [
                'recipient' => $recipient,
                'items'     => $orderItems,
                'confirm'   => false,
            ],
        ]);

        $data = $response->toArray(false);

        if (($data['code'] ?? 0) !== 200) {
            throw new \RuntimeException(
                'Printful API error: ' . ($data['error']['message'] ?? 'code ' . ($data['code'] ?? '?'))
            );
        }

        return (int) ($data['result']['id'] ?? throw new \RuntimeException('Printful order ID manquant dans la réponse'));
    }

    /**
     * Retourne tous les produits synchronisés avec leurs variantes.
     * Utile pour récupérer les sync_variant_id corrects.
     */
    public function getSyncProducts(): array
    {
        $response = $this->httpClient->request('GET', self::API_BASE . '/store/products?limit=100', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-PF-Store-Id' => $this->storeId,
            ],
        ]);

        $data = $response->toArray(false);

        if (($data['code'] ?? 0) !== 200) {
            throw new \RuntimeException(
                'Printful API error: ' . ($data['error']['message'] ?? 'code ' . ($data['code'] ?? '?'))
            );
        }

        $products = [];
        foreach ($data['result'] as $item) {
            $product = $item['sync_product'];

            // Récupérer les variantes du produit
            $varResp = $this->httpClient->request('GET', self::API_BASE . '/store/products/' . $product['id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-PF-Store-Id' => $this->storeId,
                ],
            ]);
            $varData = $varResp->toArray(false);
            $variants = [];
            foreach ($varData['result']['sync_variants'] ?? [] as $v) {
                $variants[] = [
                    'id'   => $v['id'],
                    'name' => $v['name'],
                    'sku'  => $v['sku'] ?? '',
                ];
            }

            $products[] = [
                'id'       => $product['id'],
                'name'     => $product['name'],
                'variants' => $variants,
            ];
        }

        return $products;
    }
}
