<?php
namespace App\Repository;

use App\Entity\GuideTaille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GuideTailleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuideTaille::class);
    }

    public function findBySlug(string $slug): ?GuideTaille
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
