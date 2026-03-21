<?php

namespace App\Entity;

use App\Repository\DepotVenteStockItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepotVenteStockItemRepository::class)]
#[ORM\Table(name: 'depot_vente_stock_items')]
#[ORM\UniqueConstraint(name: 'uniq_depot_variante', columns: ['depot_vente_id', 'variante_id'])]
class DepotVenteStockItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stockItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DepotVente $depotVente;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Variante $variante;

    #[ORM\Column]
    private int $quantite = 0;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDepotVente(): DepotVente { return $this->depotVente; }
    public function setDepotVente(DepotVente $d): static { $this->depotVente = $d; return $this; }

    public function getVariante(): Variante { return $this->variante; }
    public function setVariante(Variante $v): static { $this->variante = $v; return $this; }

    public function getQuantite(): int { return $this->quantite; }
    public function setQuantite(int $q): static { $this->quantite = $q; $this->updatedAt = new \DateTimeImmutable(); return $this; }

    public function addQuantite(int $delta): static { return $this->setQuantite($this->quantite + $delta); }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
