<?php

namespace App\Repository;

use App\Entity\DepotVente;
use App\Entity\DepotVenteTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DepotVenteTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepotVenteTransaction::class);
    }

    /** @return DepotVenteTransaction[] */
    public function findByDepot(DepotVente $depot, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.depotVente = :depot')
            ->setParameter('depot', $depot)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
