<?php
namespace App\Entity;

use App\Repository\GrillePrixRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrillePrixRepository::class)]
#[ORM\Table(name: 'grilles_prix')]
class GrillePrix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Lignes de tarif pour les quantités 1 à 10.
     * Format : [
     *   {'quantite': 1, 'prixFournisseur': 5.50, 'prixVente': 15.00},
     *   ...
     * ]
     */
    #[ORM\Column(type: 'json')]
    private array $lignes = [];

    public function __construct()
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->lignes[] = ['quantite' => $i, 'prixFournisseur' => null, 'prixVente' => null];
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }
    public function getLignes(): array { return $this->lignes; }
    public function setLignes(array $lignes): static { $this->lignes = $lignes; return $this; }
}
