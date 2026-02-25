<?php

namespace App\Repository;

use App\Entity\BoutiqueRelais;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BoutiqueRelaisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoutiqueRelais::class);
    }

    public function findActives(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.actif = true')
            ->orderBy('b.ville', 'ASC')
            ->addOrderBy('b.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
