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

    /**
     * Articles actifs et publics (article, collection et catégorie tous non privés et actifs).
     *
     * @return Article[]
     */
    public function findPublicFeatured(?array $collections = null, int $limit = 8): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.collection', 'col')
            ->leftJoin('col.categorie', 'cat')
            ->where('a.actif = true')
            ->andWhere('a.enVedette = true')
            ->andWhere('a.lienDirectUniquement = false')
            ->andWhere('col.id IS NULL OR (col.actif = true AND col.lienDirectUniquement = false)')
            ->andWhere('cat.id IS NULL OR (cat.actif = true AND cat.lienDirectUniquement = false)')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($collections !== null) {
            if (empty($collections)) {
                return [];
            }
            $qb->andWhere('a.collection IN (:collections)')
                ->setParameter('collections', $collections);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Articles actifs et publics (pour dépôt-vente / sélecteurs publics).
     *
     * @return Article[]
     */
    public function findPublicActives(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.collection', 'col')
            ->leftJoin('col.categorie', 'cat')
            ->where('a.actif = true')
            ->andWhere('a.lienDirectUniquement = false')
            ->andWhere('col.id IS NULL OR (col.actif = true AND col.lienDirectUniquement = false)')
            ->andWhere('cat.id IS NULL OR (cat.actif = true AND cat.lienDirectUniquement = false)')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Articles d'une collection (publics uniquement, ou tous les actifs si accès hiérarchique débloqué).
     *
     * @return Article[]
     */
    public function findByCollectionWithVisibility(\App\Entity\ProductCollection $collection, bool $includePrivate, int $limit, int $offset): array
    {
        $criteria = ['actif' => true, 'collection' => $collection];
        if (!$includePrivate) {
            $criteria['lienDirectUniquement'] = false;
        }

        return $this->findBy($criteria, ['ordre' => 'ASC', 'createdAt' => 'DESC'], $limit, $offset);
    }

    public function countByCollectionWithVisibility(\App\Entity\ProductCollection $collection, bool $includePrivate): int
    {
        $criteria = ['actif' => true, 'collection' => $collection];
        if (!$includePrivate) {
            $criteria['lienDirectUniquement'] = false;
        }

        return $this->count($criteria);
    }

    /**
     * Articles similaires dans la même collection.
     *
     * @return Article[]
     */
    public function findSimilar(Article $article, bool $includePrivate, int $limit = 4): array
    {
        $collection = $article->getCollection();
        if (!$collection) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->where('a.actif = true')
            ->andWhere('a.collection = :col')
            ->andWhere('a.id != :id')
            ->setParameter('col', $collection)
            ->setParameter('id', $article->getId())
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        if (!$includePrivate) {
            $qb->andWhere('a.lienDirectUniquement = false');
        }

        return $qb->getQuery()->getResult();
    }
}
