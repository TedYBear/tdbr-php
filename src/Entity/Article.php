<?php
namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'articles')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 300)]
    private string $nom;

    #[ORM\Column(length: 300, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $prixBase = 0.0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $prixFournisseur = null;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column]
    private bool $enVedette = false;

    #[ORM\Column]
    private bool $personnalisable = false;

    #[ORM\Column]
    private int $ordre = 0;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?ProductCollection $collection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?GrillePrix $grillePrix = null;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ArticleImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Variante::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variantes;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->images = new ArrayCollection();
        $this->variantes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getPrixBase(): float { return (float)$this->prixBase; }
    public function setPrixBase(float $p): static { $this->prixBase = $p; return $this; }
    public function getPrixFournisseur(): ?float { return $this->prixFournisseur !== null ? (float)$this->prixFournisseur : null; }
    public function setPrixFournisseur(?float $p): static { $this->prixFournisseur = $p; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $a): static { $this->actif = $a; return $this; }
    public function isEnVedette(): bool { return $this->enVedette; }
    public function setEnVedette(bool $e): static { $this->enVedette = $e; return $this; }
    public function isPersonnalisable(): bool { return $this->personnalisable; }
    public function setPersonnalisable(bool $p): static { $this->personnalisable = $p; return $this; }
    public function getOrdre(): int { return $this->ordre; }
    public function setOrdre(int $o): static { $this->ordre = $o; return $this; }
    public function getCollection(): ?ProductCollection { return $this->collection; }
    public function setCollection(?ProductCollection $c): static { $this->collection = $c; return $this; }
    public function getFournisseur(): ?Fournisseur { return $this->fournisseur; }
    public function setFournisseur(?Fournisseur $f): static { $this->fournisseur = $f; return $this; }
    public function getGrillePrix(): ?GrillePrix { return $this->grillePrix; }
    public function setGrillePrix(?GrillePrix $g): static { $this->grillePrix = $g; return $this; }
    public function getImages(): Collection { return $this->images; }
    public function addImage(ArticleImage $img): static { if (!$this->images->contains($img)) { $this->images->add($img); $img->setArticle($this); } return $this; }
    public function removeImage(ArticleImage $img): static { if ($this->images->removeElement($img)) { if ($img->getArticle() === $this) { $img->setArticle(null); } } return $this; }
    public function getVariantes(): Collection { return $this->variantes; }
    public function addVariante(Variante $v): static { if (!$this->variantes->contains($v)) { $this->variantes->add($v); $v->setArticle($this); } return $this; }
    public function removeVariante(Variante $v): static { if ($this->variantes->removeElement($v)) { if ($v->getArticle() === $this) { $v->setArticle(null); } } return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $u): static { $this->updatedAt = $u; return $this; }

    public function getFirstImageUrl(): ?string
    {
        if ($this->images->isEmpty()) return null;
        return $this->images->first()->getUrl();
    }
}
