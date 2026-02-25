<?php
namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commandes')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $numero;

    #[ORM\Column(type: 'json')]
    private array $client = [];

    #[ORM\Column(type: 'json')]
    private array $adresseLivraison = [];

    #[ORM\Column(type: 'json')]
    private array $articles = [];

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $total = 0.0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $modePaiement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'en_attente';

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $modeLivraison = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $reduction = 0.0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getNumero(): string { return $this->numero; }
    public function setNumero(string $n): static { $this->numero = $n; return $this; }
    public function getClient(): array { return $this->client; }
    public function setClient(array $c): static { $this->client = $c; return $this; }
    public function getAdresseLivraison(): array { return $this->adresseLivraison; }
    public function setAdresseLivraison(array $a): static { $this->adresseLivraison = $a; return $this; }
    public function getArticles(): array { return $this->articles; }
    public function setArticles(array $a): static { $this->articles = $a; return $this; }
    public function getTotal(): float { return (float)$this->total; }
    public function setTotal(float $t): static { $this->total = $t; return $this; }
    public function getModePaiement(): ?string { return $this->modePaiement; }
    public function setModePaiement(?string $m): static { $this->modePaiement = $m; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }
    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $id): static { $this->stripePaymentIntentId = $id; return $this; }
    public function getModeLivraison(): ?array { return $this->modeLivraison; }
    public function setModeLivraison(?array $m): static { $this->modeLivraison = $m; return $this; }
    public function getReduction(): float { return (float) $this->reduction; }
    public function setReduction(float $r): static { $this->reduction = $r; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $c): static { $this->createdAt = $c; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $u): static { $this->updatedAt = $u; return $this; }
}
