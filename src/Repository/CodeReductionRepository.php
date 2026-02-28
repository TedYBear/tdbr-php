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

    public function findActiveGlobal(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('c')
            ->where('c.user IS NULL')
            ->andWhere('c.statut = :statut')
            ->andWhere('c.dateDebut IS NULL OR c.dateDebut <= :now')
            ->andWhere('c.dateExpiration IS NULL OR c.dateExpiration > :now')
            ->setParameter('statut', 'actif')
            ->setParameter('now', $now)
            ->orderBy('c.montant', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveForUser(User $user): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('c')
            ->where('(c.user = :user OR c.user IS NULL)')
            ->andWhere('c.statut = :statut')
            ->andWhere('c.dateDebut IS NULL OR c.dateDebut <= :now')
            ->andWhere('c.dateExpiration IS NULL OR c.dateExpiration > :now')
            ->setParameter('user', $user)
            ->setParameter('statut', 'actif')
            ->setParameter('now', $now)
            ->orderBy('c.user', 'ASC')
            ->addOrderBy('c.montant', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countCampaignGift(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.isCampaignGift = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasCampaignGiftForEmail(string $email): bool
    {
        $count = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.isCampaignGift = true')
            ->andWhere('c.recipientEmail = :email')
            ->setParameter('email', strtolower($email))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
