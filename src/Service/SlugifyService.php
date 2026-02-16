<?php

namespace App\Service;

class SlugifyService
{
    public function slugify(string $text): string
    {
        // Translittération des caractères spéciaux
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);

        // Conversion en minuscules
        $text = strtolower($text);

        // Remplacement des caractères non alphanumériques par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Suppression des tirets en début et fin
        $text = trim($text, '-');

        return $text;
    }
}
