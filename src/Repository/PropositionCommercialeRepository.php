<?php
namespace App\Repository;

use App\Entity\PropositionCommerciale;
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
}
