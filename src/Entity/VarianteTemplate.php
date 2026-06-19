<?php
namespace App\Entity;

use App\Repository\VarianteTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VarianteTemplateRepository::class)]
#[ORM\Table(name: 'variante_templates')]
class VarianteTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** URL du PDF guide des tailles (optionnel) */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $guideTaillePdfUrl = null;

    #[ORM\ManyToMany(targetEntity: Caracteristique::class)]
    #[ORM\JoinTable(name: 'template_caracteristiques')]
    private Collection $caracteristiques;

    /** Types de personnalisation proposés pour les articles utilisant ce template (peut être vide). */
    #[ORM\ManyToMany(targetEntity: TypePersonnalisation::class)]
    #[ORM\JoinTable(name: 'template_personnalisations')]
    #[ORM\OrderBy(['ordre' => 'ASC', 'nom' => 'ASC'])]
    private Collection $personnalisations;

    public function __construct()
    {
        $this->caracteristiques = new ArrayCollection();
        $this->personnalisations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getGuideTaillePdfUrl(): ?string { return $this->guideTaillePdfUrl; }
    public function setGuideTaillePdfUrl(?string $u): static { $this->guideTaillePdfUrl = $u; return $this; }
    public function getCaracteristiques(): Collection { return $this->caracteristiques; }
    public function addCaracteristique(Caracteristique $c): static { if (!$this->caracteristiques->contains($c)) { $this->caracteristiques->add($c); } return $this; }
    public function removeCaracteristique(Caracteristique $c): static { $this->caracteristiques->removeElement($c); return $this; }

    public function getPersonnalisations(): Collection { return $this->personnalisations; }
    public function addPersonnalisation(TypePersonnalisation $p): static { if (!$this->personnalisations->contains($p)) { $this->personnalisations->add($p); } return $this; }
    public function removePersonnalisation(TypePersonnalisation $p): static { $this->personnalisations->removeElement($p); return $this; }
}
