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
     * Ajoute un article au panier
     */
    public function addItem(array $article, int $quantity = 1, ?array $variant = null): void
    {
        $cart = $this->getCart();
        $itemId = (string)$article['_id'] . ($variant ? '-' . ($variant['id'] ?? $variant['_id']) : '');

        if (isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] += $quantity;
        } else {
            $cart[$itemId] = [
                'article' => $article,
                'variant' => $variant,
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
            }
            $this->session->set('cart', $cart);
        }
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
            $price = $item['variant']['prix'] ?? $item['article']['prix'];
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
