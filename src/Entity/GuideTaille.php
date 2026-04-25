<?php
namespace App\Entity;

use App\Repository\GuideTailleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GuideTailleRepository::class)]
#[ORM\Table(name: 'guides_tailles')]
class GuideTaille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(length: 220, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** URL ou chemin de l'image schéma A/B/C */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageDiagramme = null;

    /**
     * Points de mesure illustrés sur le schéma.
     * Format : [{lettre: 'A', nom: 'Longueur', description: '…'}, …]
     */
    #[ORM\Column(type: 'json')]
    private array $mesures = [];

    /**
     * Entêtes du tableau de tailles.
     * Format : ['Longueur', 'Largeur', 'Longueur des manches']
     */
    #[ORM\Column(type: 'json')]
    private array $colonnes = [];

    /**
     * Lignes du tableau de tailles.
     * Format : [{taille: 'S', valeurs: [71, 45.7, 39.7]}, …]
     */
    #[ORM\Column(type: 'json')]
    private array $lignes = [];

    #[ORM\Column(length: 20)]
    private string $unite = 'cm';

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $n): static { $this->nom = $n; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $s): static { $this->slug = $s; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getImageDiagramme(): ?string { return $this->imageDiagramme; }
    public function setImageDiagramme(?string $i): static { $this->imageDiagramme = $i; return $this; }
    public function getMesures(): array { return $this->mesures; }
    public function setMesures(array $m): static { $this->mesures = $m; return $this; }
    public function getColonnes(): array { return $this->colonnes; }
    public function setColonnes(array $c): static { $this->colonnes = $c; return $this; }
    public function getLignes(): array { return $this->lignes; }
    public function setLignes(array $l): static { $this->lignes = $l; return $this; }
    public function getUnite(): string { return $this->unite; }
    public function setUnite(string $u): static { $this->unite = $u; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $a): static { $this->actif = $a; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $u): static { $this->updatedAt = $u; return $this; }
}
