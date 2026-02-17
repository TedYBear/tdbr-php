<?php
namespace App\Entity;

use App\Repository\VarianteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VarianteRepository::class)]
#[ORM\Table(name: 'variantes')]
class Variante
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'variantes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $prix = null;

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Column]
    private bool $actif = true;

    public function getId(): ?int { return $this->id; }
    public function getArticle(): ?Article { return $this->article; }
    public function setArticle(?Article $a): static { $this->article = $a; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSku(): ?string { return $this->sku; }
    public function setSku(?string $sku): static { $this->sku = $sku; return $this; }
    public function getPrix(): ?float { return $this->prix !== null ? (float)$this->prix : null; }
    public function setPrix(?float $p): static { $this->prix = $p; return $this; }
    public function getStock(): int { return $this->stock; }
    public function setStock(int $s): static { $this->stock = $s; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $a): static { $this->actif = $a; return $this; }
}
