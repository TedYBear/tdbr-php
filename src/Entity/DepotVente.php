<?php

namespace App\Entity;

use App\Repository\DepotVenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepotVenteRepository::class)]
#[ORM\Table(name: 'depot_ventes')]
class DepotVente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nom;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $fondDeCaisse = 0.0;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'depotVente', targetEntity: DepotVenteStockItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $stockItems;

    #[ORM\OneToMany(mappedBy: 'depotVente', targetEntity: DepotVenteTransaction::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $transactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->stockItems = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $v): static { $this->adresse = $v; return $this; }

    public function getCodePostal(): ?string { return $this->codePostal; }
    public function setCodePostal(?string $v): static { $this->codePostal = $v; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $v): static { $this->ville = $v; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $v): static { $this->telephone = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): static { $this->email = $v; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getFondDeCaisse(): float { return (float)$this->fondDeCaisse; }
    public function setFondDeCaisse(float $v): static { $this->fondDeCaisse = $v; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $v): static { $this->actif = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getStockItems(): Collection { return $this->stockItems; }
    public function getTransactions(): Collection { return $this->transactions; }

    /** Retourne le StockItem pour une variante donnée, ou null */
    public function getStockItemForVariante(Variante $variante): ?DepotVenteStockItem
    {
        foreach ($this->stockItems as $item) {
            if ($item->getVariante() === $variante) {
                return $item;
            }
        }
        return null;
    }

    /** Quantité totale en stock (toutes variantes confondues) */
    public function getTotalStock(): int
    {
        $total = 0;
        foreach ($this->stockItems as $item) {
            $total += $item->getQuantite();
        }
        return $total;
    }
}
