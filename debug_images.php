<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$mongoService = $container->get('App\Service\MongoDBService');

// Récupérer un article
$collection = $mongoService->getCollection('articles');
$article = $collection->findOne(['actif' => true]);

if ($article) {
    echo "Article trouvé: " . $article['nom'] . "\n";
    echo "Structure des images:\n";
    echo "Type: " . gettype($article['images']) . "\n";

    if (isset($article['images'])) {
        echo "Nombre d'images: " . count($article['images']) . "\n";
        echo "Contenu brut:\n";
        var_dump($article['images']);

        echo "\nAprès conversion toArray:\n";
        $articleArray = App\Service\MongoDBService::toArray($article);
        var_dump($articleArray['images']);
    } else {
        echo "Pas d'images dans cet article\n";
    }
} else {
    echo "Aucun article trouvé\n";
}
