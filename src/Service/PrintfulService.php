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
        $catalogCache = []; // [catalogProductId => [catalogVariantId => ['color' => ..., 'size' => ...]]]

        foreach ($data['result'] as $product) {
            // Récupérer les variantes du produit
            $varResp = $this->httpClient->request('GET', self::API_BASE . '/store/products/' . $product['id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-PF-Store-Id' => $this->storeId,
                ],
            ]);
            $varData    = $varResp->toArray(false);
            $variants   = [];
            $mockupUrls = [];

            foreach ($varData['result']['sync_variants'] ?? [] as $v) {
                // Récupérer les attributs explicites du catalogue (color/size)
                $catProductId = (int)($v['product']['product_id'] ?? 0);
                $catVariantId = (int)($v['variant_id'] ?? $v['product']['variant_id'] ?? 0);

                if ($catProductId > 0 && !isset($catalogCache[$catProductId])) {
                    $catalogCache[$catProductId] = $this->fetchCatalogVariantsInfo($catProductId);
                }

                $catalogInfo = $catalogCache[$catProductId][$catVariantId] ?? [];

                $variants[] = [
                    'id'          => $v['id'],
                    'name'        => $v['name'],
                    'productName' => $v['product']['name'] ?? null,
                    // Couleur et taille EXPLICITES depuis le catalogue Printful
                    'color'       => $catalogInfo['color'] ?? null,
                    'size'        => $catalogInfo['size'] ?? null,
                    'sku'         => $v['sku'] ?? '',
                    'synced'      => $v['synced'] ?? false,
                ];
                // Collecter les preview_url de maquettes (dédoublonnées)
                foreach ($v['files'] ?? [] as $file) {
                    $url = $file['preview_url'] ?? null;
                    if ($url && !in_array($url, $mockupUrls, true)) {
                        $mockupUrls[] = $url;
                    }
                }
            }

            $products[] = [
                'id'        => $product['id'],
                'name'      => $product['name'],
                'thumbnail' => $product['thumbnail_url'] ?? null,
                'mockups'   => $mockupUrls,
                'variants'  => $variants,
            ];
        }

        return $products;
    }

    /**
     * Récupère pour un produit catalogue Printful la table [variantId => [color, size]].
     * Endpoint public — n'a pas besoin du store id mais on garde l'auth bearer.
     */
    private function fetchCatalogVariantsInfo(int $catalogProductId): array
    {
        try {
            $resp = $this->httpClient->request('GET', self::API_BASE . '/products/' . $catalogProductId, [
                'auth_bearer' => $this->apiKey,
            ]);
            $data = $resp->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        $info = [];
        foreach ($data['result']['variants'] ?? [] as $v) {
            $info[(int)$v['id']] = [
                'color' => $v['color'] ?? null,
                'size'  => $v['size'] ?? null,
            ];
        }
        return $info;
    }
}
