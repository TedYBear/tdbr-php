<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    /**
     * Ajoute un article au panier.
     * $choices = ['Taille' => 'M', 'Couleur' => 'Rouge']
     */
    public function addItem(array $article, int $quantity = 1, array $choices = []): void
    {
        $cart = $this->getCart();

        ksort($choices);
        $choicesHash = $choices ? substr(md5(json_encode($choices)), 0, 8) : '';
        $itemId = $article['id'] . ($choicesHash ? '-' . $choicesHash : '');

        if (isset($cart[$itemId])) {
            $newQty = $cart[$itemId]['quantity'] + $quantity;
            $cart[$itemId]['quantity'] = $newQty;
            $paliers = $cart[$itemId]['article']['paliers'] ?? [];
            if (!empty($paliers)) {
                $resolved = $this->resolveUnitPrice($paliers, $newQty);
                if ($resolved !== null) {
                    $cart[$itemId]['article']['prix'] = $resolved;
                }
            }
        } else {
            $paliers = $article['paliers'] ?? [];
            if (!empty($paliers)) {
                $resolved = $this->resolveUnitPrice($paliers, $quantity);
                if ($resolved !== null) {
                    $article['prix'] = $resolved;
                }
            }
            $cart[$itemId] = [
                'article' => $article,
                'choices' => $choices,
                'quantity' => $quantity
            ];
        }

        $this->session->set('cart', $cart);
    }

    /**
     * Supprime un article du panier
     */
    public function removeItem(string $itemId): void
    {
        $cart = $this->getCart();

        if (isset($cart[$itemId])) {
            unset($cart[$itemId]);
            $this->session->set('cart', $cart);
        }
    }

    /**
     * Met à jour la quantité d'un article
     */
    public function updateQuantity(string $itemId, int $quantity): void
    {
        $cart = $this->getCart();

        if (isset($cart[$itemId])) {
            if ($quantity <= 0) {
                unset($cart[$itemId]);
            } else {
                $cart[$itemId]['quantity'] = $quantity;
                $paliers = $cart[$itemId]['article']['paliers'] ?? [];
                if (!empty($paliers)) {
                    $resolved = $this->resolveUnitPrice($paliers, $quantity);
                    if ($resolved !== null) {
                        $cart[$itemId]['article']['prix'] = $resolved;
                    }
                }
            }
            $this->session->set('cart', $cart);
        }
    }

    /**
     * Retourne le prix unitaire correspondant à la quantité selon les paliers tarifaires.
     * Les paliers sont parcourus dans l'ordre croissant ; le dernier dont min <= quantité est retenu.
     */
    private function resolveUnitPrice(array $paliers, int $quantity): ?float
    {
        $resolved = null;
        foreach ($paliers as $palier) {
            if ($quantity >= ($palier['min'] ?? 0) && isset($palier['prixVente']) && $palier['prixVente'] !== null) {
                $resolved = (float)$palier['prixVente'];
            }
        }
        return $resolved;
    }

    /**
     * Récupère le panier
     */
    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    /**
     * Calcule le total du panier
     */
    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $price = $item['article']['prix'] ?? 0;
            $total += $price * $item['quantity'];
        }
        return $total;
    }

    /**
     * Calcule le nombre total d'articles dans le panier
     */
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item['quantity'];
        }
        return $total;
    }

    /**
     * Vide le panier
     */
    public function clear(): void
    {
        $this->session->remove('cart');
    }

    /**
     * Vérifie si le panier est vide
     */
    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }
}
