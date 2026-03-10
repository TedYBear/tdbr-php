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

    /**
     * Delta de prix par rapport au prix de base de l'article (peut être positif ou négatif).
     * Exemple : +2.00 pour un surcoût XL, -1.50 pour une réduction XS.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $deltaPrix = null;

    /**
     * Valeurs structurées de cette variante.
     * Exemple : {"Taille": "S", "Couleur": "Rouge"}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $valeurs = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $printfulVariantId = null;

    #[ORM\Column]
    private bool $actif = true;

    public function getId(): ?int { return $this->id; }
    public function getArticle(): ?Article { return $this->article; }
    public function setArticle(?Article $a): static { $this->article = $a; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSku(): ?string { return $this->sku; }
    public function setSku(?string $sku): static { $this->sku = $sku; return $this; }
    public function getDeltaPrix(): ?float { return $this->deltaPrix !== null ? (float)$this->deltaPrix : null; }
    public function setDeltaPrix(?float $d): static { $this->deltaPrix = $d; return $this; }
    public function getValeurs(): ?array { return $this->valeurs; }
    public function setValeurs(?array $v): static { $this->valeurs = $v; return $this; }
    public function getPrintfulVariantId(): ?int { return $this->printfulVariantId !== null ? (int)$this->printfulVariantId : null; }
    public function setPrintfulVariantId(?int $id): static { $this->printfulVariantId = $id !== null ? (string)$id : null; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $a): static { $this->actif = $a; return $this; }
}
