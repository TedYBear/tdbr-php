<?php

namespace App\Repository;

use App\Entity\PageView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PageViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageView::class);
    }

    public function countViews(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('pv')
            ->select('COUNT(pv.id)')
            ->where('pv.viewedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUniqueVisitors(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('pv')
            ->select('COUNT(DISTINCT pv.sessionId)')
            ->where('pv.viewedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function topArticles(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.entitySlug, pv.entityId, COUNT(pv.id) AS vues')
            ->where('pv.viewedAt BETWEEN :from AND :to')
            ->andWhere('pv.entityType = :type')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('type', 'article')
            ->groupBy('pv.entitySlug, pv.entityId')
            ->orderBy('vues', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function topPages(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.routeName, COUNT(pv.id) AS vues')
            ->where('pv.viewedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('pv.routeName')
            ->orderBy('vues', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function topCategories(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.entitySlug, pv.entityId, COUNT(pv.id) AS vues')
            ->where('pv.viewedAt BETWEEN :from AND :to')
            ->andWhere('pv.entityType = :type')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('type', 'categorie')
            ->groupBy('pv.entitySlug, pv.entityId')
            ->orderBy('vues', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function topCollections(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
        return $this->createQueryBuilder('pv')
            ->select('pv.entitySlug, pv.entityId, COUNT(pv.id) AS vues')
            ->where('pv.viewedAt BETWEEN :from AND :to')
            ->andWhere('pv.entityType = :type')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('type', 'collection')
            ->groupBy('pv.entitySlug, pv.entityId')
            ->orderBy('vues', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function viewsByDay(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<SQL
            SELECT
                DATE(viewed_at)               AS jour,
                COUNT(id)                     AS vues,
                COUNT(DISTINCT session_id)    AS visiteurs
            FROM page_views
            WHERE viewed_at BETWEEN :from AND :to
            GROUP BY DATE(viewed_at)
            ORDER BY jour DESC
            LIMIT :limit
        SQL;

        return $conn->executeQuery($sql, [
            'from'  => $from->format('Y-m-d H:i:s'),
            'to'    => $to->format('Y-m-d H:i:s'),
            'limit' => $limit,
        ], [
            'limit' => \Doctrine\DBAL\ParameterType::INTEGER,
        ])->fetchAllAssociative();
    }

    public function mostViewedPageAllTime(): ?array
    {
        $result = $this->createQueryBuilder('pv')
            ->select('pv.routeName, COUNT(pv.id) AS vues')
            ->groupBy('pv.routeName')
            ->orderBy('vues', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        return $result ?: null;
    }
}
