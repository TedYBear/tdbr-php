<?php

namespace App\Entity;

use App\Repository\DepotVenteTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepotVenteTransactionRepository::class)]
#[ORM\Table(name: 'depot_vente_transactions')]
class DepotVenteTransaction
{
    const TYPE_VENTE        = 'vente';
    const TYPE_REASSORT     = 'reassort';
    const TYPE_FOND_AJOUT   = 'fond_ajout';
    const TYPE_FOND_RETRAIT = 'fond_retrait';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DepotVente $depotVente;

    /** vente | reassort | fond_ajout | fond_retrait */
    #[ORM\Column(length: 20)]
    private string $type;

    /** Variation du fond de caisse (positif = ajout, négatif = retrait). Null pour réassort. */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $montantFond = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: DepotVenteTransactionLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getDepotVente(): DepotVente { return $this->depotVente; }
    public function setDepotVente(DepotVente $d): static { $this->depotVente = $d; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }

    public function getMontantFond(): ?float { return $this->montantFond !== null ? (float)$this->montantFond : null; }
    public function setMontantFond(?float $v): static { $this->montantFond = $v; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $v): static { $this->note = $v; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $u): static { $this->createdBy = $u; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(DepotVenteTransactionLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setTransaction($this);
        }
        return $this;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_VENTE        => 'Vente',
            self::TYPE_REASSORT     => 'Réassort',
            self::TYPE_FOND_AJOUT   => 'Fond de caisse +',
            self::TYPE_FOND_RETRAIT => 'Fond de caisse -',
            default                 => $this->type,
        };
    }

    /** Total des prix réels pour une vente */
    public function getTotalPrixReel(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += (float)($ligne->getPrixReel() ?? 0);
        }
        return $total;
    }

    /** Total des prix estimés */
    public function getTotalPrixEstime(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += (float)($ligne->getPrixEstime() ?? 0);
        }
        return $total;
    }
}
