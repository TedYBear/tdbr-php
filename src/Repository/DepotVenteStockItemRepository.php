<?php

namespace App\Repository;

use App\Entity\DepotVente;
use App\Entity\DepotVenteStockItem;
use App\Entity\Variante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DepotVenteStockItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepotVenteStockItem::class);
    }

    public function findOneByDepotAndVariante(DepotVente $depot, Variante $variante): ?DepotVenteStockItem
    {
        return $this->findOneBy(['depotVente' => $depot, 'variante' => $variante]);
    }
}
