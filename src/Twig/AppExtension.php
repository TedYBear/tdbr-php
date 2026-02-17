<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice']),
            new TwigFilter('date_french', [$this, 'formatDateFrench']),
            new TwigFilter('truncate', [$this, 'truncate']),
            new TwigFilter('to_string', [$this, 'toString']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_exists', [$this, 'assetExists']),
        ];
    }

    /**
     * Formatte un prix en euros
     */
    public function formatPrice(float $price): string
    {
        return number_format($price, 2, ',', ' ') . ' €';
    }

    /**
     * Formatte une date en format français
     */
    public function formatDateFrench(\DateTimeInterface $date, string $format = 'd/m/Y à H:i'): string
    {
        return $date->toDateTime()->format($format);
    }

    /**
     * Tronque un texte à une longueur donnée
     */
    public function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }

    /**
     * Vérifie si un asset existe
     */
    public function assetExists(string $path): bool
    {
        $publicPath = __DIR__ . '/../../public/' . ltrim($path, '/');
        return file_exists($publicPath);
    }

    /**
     * Convertit une valeur en string de façon robuste
     * Gère les arrays, objets BSON, etc.
     */
    public function toString($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Si c'est un array, retourner le premier élément s'il existe
            return $this->toString(reset($value) ?: '');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return '';
    }
}
