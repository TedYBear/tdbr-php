<?php
namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Génère un slug unique en BDD à partir d'un slug de base.
     * Ajoute -2, -3, … en cas de collision.
     *
     * @param int|null   $exceptId   ID à exclure (cas de modification d'un article existant)
     * @param array      $extraTaken Slugs déjà utilisés dans le batch courant (pas encore flushés)
     */
    public function generateUniqueSlug(string $base, ?int $exceptId = null, array $extraTaken = []): string
    {
        $base = trim($base);
        if ($base === '') $base = 'article';

        $slug = $base;
        $i = 2;
        while ($this->slugIsTaken($slug, $exceptId) || in_array($slug, $extraTaken, true)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function slugIsTaken(string $slug, ?int $exceptId): bool
    {
        $existing = $this->findOneBy(['slug' => $slug]);
        if (!$existing) return false;
        return $exceptId === null || $existing->getId() !== $exceptId;
    }
}
