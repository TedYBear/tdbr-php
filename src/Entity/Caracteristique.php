<?php
namespace App\Entity;

use App\Repository\CaracteristiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaracteristiqueRepository::class)]
#[ORM\Table(name: 'caracteristiques')]
class Caracteristique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(length: 50)]
    private string $type = 'text';

    #[ORM\Column]
    private bool $obligatoire = false;

    #[ORM\OneToMany(mappedBy: 'caracteristique', targetEntity: CaracteristiqueValeur::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $valeurs;

    public function __construct()
    {
        $this->valeurs = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function isObligatoire(): bool { return $this->obligatoire; }
    public function setObligatoire(bool $o): static { $this->obligatoire = $o; return $this; }
    public function getValeurs(): Collection { return $this->valeurs; }
    public function getValeursArray(): array { return $this->valeurs->map(fn($v) => $v->getValeur())->toArray(); }
    public function setValeursFromArray(array $valeurs): static
    {
        $this->valeurs->clear();
        foreach ($valeurs as $valeur) {
            $v = new CaracteristiqueValeur();
            $v->setValeur($valeur)->setCaracteristique($this);
            $this->valeurs->add($v);
        }
        return $this;
    }
}
