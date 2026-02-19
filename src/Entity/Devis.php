<?php
namespace App\Entity;

use App\Repository\DevisRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DevisRepository::class)]
#[ORM\Table(name: 'devis')]
class Devis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(length: 200)]
    private string $email;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text')]
    private string $concept;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contexte = null;

    #[ORM\Column(type: 'json')]
    private array $supports = [];

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $autreSupport = null;

    #[ORM\Column(length: 50)]
    private string $quantite;

    #[ORM\Column(length: 50)]
    private string $moyenContact;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $messageAdditionnel = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'nouveau';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notesAdmin = null;

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
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }
    public function getConcept(): string { return $this->concept; }
    public function setConcept(string $concept): static { $this->concept = $concept; return $this; }
    public function getContexte(): ?string { return $this->contexte; }
    public function setContexte(?string $contexte): static { $this->contexte = $contexte; return $this; }
    public function getSupports(): array { return $this->supports; }
    public function setSupports(array $supports): static { $this->supports = $supports; return $this; }
    public function getAutreSupport(): ?string { return $this->autreSupport; }
    public function setAutreSupport(?string $autreSupport): static { $this->autreSupport = $autreSupport; return $this; }
    public function getQuantite(): string { return $this->quantite; }
    public function setQuantite(string $quantite): static { $this->quantite = $quantite; return $this; }
    public function getMoyenContact(): string { return $this->moyenContact; }
    public function setMoyenContact(string $moyenContact): static { $this->moyenContact = $moyenContact; return $this; }
    public function getMessageAdditionnel(): ?string { return $this->messageAdditionnel; }
    public function setMessageAdditionnel(?string $msg): static { $this->messageAdditionnel = $msg; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getNotesAdmin(): ?string { return $this->notesAdmin; }
    public function setNotesAdmin(?string $notes): static { $this->notesAdmin = $notes; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
