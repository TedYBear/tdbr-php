<?php
namespace App\Entity;

use App\Repository\PropositionCommercialeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropositionCommercialeRepository::class)]
#[ORM\Table(name: 'propositions_commerciales')]
class PropositionCommerciale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $prixTotal;

    #[ORM\Column(length: 255)]
    private string $clientEmail;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientNom = null;

    /** brouillon | envoyee | acceptee | payee */
    #[ORM\Column(length: 50)]
    private string $statut = 'brouillon';

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\ManyToOne(targetEntity: DemandeSurMesure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DemandeSurMesure $demandeSurMesure = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Commande $commande = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int { return $this->id; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }
    public function getPrixTotal(): float { return (float)$this->prixTotal; }
    public function setPrixTotal(float $p): static { $this->prixTotal = $p; return $this; }
    public function getClientEmail(): string { return $this->clientEmail; }
    public function setClientEmail(string $e): static { $this->clientEmail = $e; return $this; }
    public function getClientNom(): ?string { return $this->clientNom; }
    public function setClientNom(?string $n): static { $this->clientNom = $n; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }
    public function getToken(): string { return $this->token; }
    public function setToken(string $t): static { $this->token = $t; return $this; }
    public function getDemandeSurMesure(): ?DemandeSurMesure { return $this->demandeSurMesure; }
    public function setDemandeSurMesure(?DemandeSurMesure $d): static { $this->demandeSurMesure = $d; return $this; }
    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $c): static { $this->commande = $c; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $u): static { $this->updatedAt = $u; return $this; }
}
