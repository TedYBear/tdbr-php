<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'depot_vente_transaction_lignes')]
class DepotVenteTransactionLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DepotVenteTransaction $transaction;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Variante $variante;

    /** Libellé snapshot au moment de la transaction */
    #[ORM\Column(length: 300)]
    private string $varianteLabel;

    #[ORM\Column]
    private int $quantite;

    /** Prix estimé total (qté × prix unitaire selon grille) */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $prixEstime = null;

    /** Prix réel total encaissé (uniquement pour les ventes) */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $prixReel = null;

    public function getId(): ?int { return $this->id; }

    public function getTransaction(): DepotVenteTransaction { return $this->transaction; }
    public function setTransaction(DepotVenteTransaction $t): static { $this->transaction = $t; return $this; }

    public function getVariante(): ?Variante { return $this->variante; }
    public function setVariante(?Variante $v): static { $this->variante = $v; return $this; }

    public function getVarianteLabel(): string { return $this->varianteLabel; }
    public function setVarianteLabel(string $v): static { $this->varianteLabel = $v; return $this; }

    public function getQuantite(): int { return $this->quantite; }
    public function setQuantite(int $q): static { $this->quantite = $q; return $this; }

    public function getPrixEstime(): ?float { return $this->prixEstime !== null ? (float)$this->prixEstime : null; }
    public function setPrixEstime(?float $v): static { $this->prixEstime = $v; return $this; }

    public function getPrixReel(): ?float { return $this->prixReel !== null ? (float)$this->prixReel : null; }
    public function setPrixReel(?float $v): static { $this->prixReel = $v; return $this; }
}
