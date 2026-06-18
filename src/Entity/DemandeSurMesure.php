<?php
namespace App\Entity;

use App\Repository\DemandeSurMesureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeSurMesureRepository::class)]
#[ORM\Table(name: 'demandes_sur_mesure')]
class DemandeSurMesure
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

    /**
     * Compte rattaché si la demande a été soumise par un utilisateur connecté.
     * Null pour les demandes invité.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * Produit concerné lorsque la demande provient de la modale d'une fiche produit.
     * Null pour les demandes de devis générique.
     */
    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Article $article = null;

    /** Origine de la demande : 'devis' (formulaire générique) ou 'fiche_produit' (modale). */
    #[ORM\Column(length: 30, options: ['default' => 'devis'])]
    private string $source = 'devis';

    /**
     * Détail de personnalisation produit : {"modele": "...", "couleur": "...", "taille": "...", "autre": "..."}.
     * Null pour les demandes de devis générique.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $personnalisation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $concept = null;

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
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getArticle(): ?Article { return $this->article; }
    public function setArticle(?Article $article): static { $this->article = $article; return $this; }
    public function getSource(): string { return $this->source; }
    public function setSource(string $source): static { $this->source = $source; return $this; }
    public function getPersonnalisation(): ?array { return $this->personnalisation; }
    public function setPersonnalisation(?array $personnalisation): static { $this->personnalisation = $personnalisation; return $this; }
    public function getConcept(): ?string { return $this->concept; }
    public function setConcept(?string $concept): static { $this->concept = $concept; return $this; }
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
