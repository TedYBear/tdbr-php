<?php

namespace App\Twig;

use MongoDB\BSON\UTCDateTime;
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
     * Formatte une date MongoDB en format français
     */
    public function formatDateFrench(UTCDateTime $date, string $format = 'd/m/Y à H:i'): string
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
}
