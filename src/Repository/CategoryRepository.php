<?php
namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Catégories actives et visibles dans le catalogue public (hors lien direct uniquement).
     *
     * @return Category[]
     */
    public function findPublicActives(): array
    {
        return $this->findBy(
            ['actif' => true, 'lienDirectUniquement' => false],
            ['ordre' => 'ASC', 'nom' => 'ASC']
        );
    }
}
