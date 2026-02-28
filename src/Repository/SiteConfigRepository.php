<?php

namespace App\Repository;

use App\Entity\SiteConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SiteConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteConfig::class);
    }

    /**
     * Retourne la configuration unique du site, ou la crée si elle n'existe pas.
     */
    public function getConfig(): SiteConfig
    {
        $config = $this->findOneBy([]);

        if (!$config) {
            $config = new SiteConfig();
            $this->getEntityManager()->persist($config);
            $this->getEntityManager()->flush();
        }

        return $config;
    }
}
