<?php
namespace App\Repository;

use App\Entity\ProductCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCollection::class);
    }

    /**
     * Collections actives et publiques d'une catégorie (hors lien direct uniquement).
     *
     * @return ProductCollection[]
     */
    public function findPublicByCategory(\App\Entity\Category $category): array
    {
        return $this->findBy(
            ['actif' => true, 'lienDirectUniquement' => false, 'categorie' => $category],
            ['ordre' => 'ASC']
        );
    }
}
