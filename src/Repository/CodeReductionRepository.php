<?php

namespace App\Repository;

use App\Entity\CodeReduction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CodeReductionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodeReduction::class);
    }

    public function findActiveForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->andWhere('c.dateExpiration IS NULL OR c.dateExpiration > :now')
            ->setParameter('user', $user)
            ->setParameter('statut', 'actif')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.montant', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
