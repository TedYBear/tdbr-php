<?php

namespace App\Repository;

use App\Entity\DepotVente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DepotVenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepotVente::class);
    }

    /** @return DepotVente[] */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.actif = true')
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
