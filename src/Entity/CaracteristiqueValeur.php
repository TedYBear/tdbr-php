<?php
namespace App\Entity;

use App\Repository\CaracteristiqueValeurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaracteristiqueValeurRepository::class)]
#[ORM\Table(name: 'caracteristique_valeurs')]
class CaracteristiqueValeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'valeurs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Caracteristique $caracteristique = null;

    #[ORM\Column(length: 200)]
    private string $valeur;

    public function getId(): ?int { return $this->id; }
    public function getCaracteristique(): ?Caracteristique { return $this->caracteristique; }
    public function setCaracteristique(?Caracteristique $c): static { $this->caracteristique = $c; return $this; }
    public function getValeur(): string { return $this->valeur; }
    public function setValeur(string $valeur): static { $this->valeur = $valeur; return $this; }
}
