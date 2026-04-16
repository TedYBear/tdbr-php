<?php
namespace App\Repository;

use App\Entity\PropositionCommerciale;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropositionCommercialeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropositionCommerciale::class);
    }

    public function findByToken(string $token): ?PropositionCommerciale
    {
        return $this->findOneBy(['token' => $token]);
    }

    /** @return PropositionCommerciale[] */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
