<?php
namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Commandes dont l'email client correspond mais qui ne sont pas encore liées à un compte.
     * @return Commande[]
     */
    public function findUnlinkedByEmail(string $email): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $ids  = $conn->fetchFirstColumn(
            'SELECT id FROM commandes WHERE user_id IS NULL AND JSON_EXTRACT(client, \'$.email\') = ?',
            [$email]
        );

        if (!$ids) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes liées à ce compte (via user OU via email client).
     * @return Commande[]
     */
    public function findAllByEmail(string $email): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $ids  = $conn->fetchFirstColumn(
            'SELECT id FROM commandes WHERE JSON_EXTRACT(client, \'$.email\') = ?',
            [$email]
        );

        if (!$ids) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
