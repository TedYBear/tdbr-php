<?php

namespace App\Entity;

use App\Repository\CodeReductionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodeReductionRepository::class)]
#[ORM\Table(name: 'codes_reduction')]
class CodeReduction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $code;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $montant;

    #[ORM\Column(length: 20)]
    private string $statut = 'actif';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Commande $commande = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getMontant(): float { return (float) $this->montant; }
    public function setMontant(float $montant): static { $this->montant = $montant; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getDateDebut(): ?\DateTimeImmutable { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeImmutable $date): static { $this->dateDebut = $date; return $this; }
    public function getDateExpiration(): ?\DateTimeImmutable { return $this->dateExpiration; }
    public function setDateExpiration(?\DateTimeImmutable $date): static { $this->dateExpiration = $date; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function isGlobal(): bool { return $this->user === null; }
    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $commande): static { $this->commande = $commande; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isActif(): bool
    {
        if ($this->statut !== 'actif') {
            return false;
        }
        $now = new \DateTimeImmutable();
        if ($this->dateDebut !== null && $this->dateDebut > $now) {
            return false;
        }
        if ($this->dateExpiration !== null && $this->dateExpiration <= $now) {
            return false;
        }
        return true;
    }
}
