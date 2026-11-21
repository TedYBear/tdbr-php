<?php

namespace App\Repository;

use App\Entity\CampagneCadeau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CampagneCadeauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampagneCadeau::class);
    }

    /**
     * Retourne les campagnes actuellement actives et non expirées.
     *
     * @return CampagneCadeau[]
     */
    public function findEnCours(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('c')
            ->where('c.actif = true')
            ->andWhere('c.dateFinActivation IS NULL OR c.dateFinActivation >= :now')
            ->setParameter('now', $now)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
