<?php

namespace App\Entity;

use App\Repository\CampagneCadeauRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampagneCadeauRepository::class)]
#[ORM\Table(name: 'campagnes_cadeau')]
class CampagneCadeau
{
    public const DECLENCHEUR_PREMIERE_COMMANDE = 'premiere_commande';
    public const DECLENCHEUR_TOUTES_COMMANDES  = 'toutes_commandes';
    public const DECLENCHEUR_MONTANT_MIN       = 'montant_min';
    public const DECLENCHEUR_NOMBRE_COMMANDES  = 'nombre_commandes';

    public const DECLENCHEURS = [
        self::DECLENCHEUR_PREMIERE_COMMANDE => 'Première commande du client',
        self::DECLENCHEUR_TOUTES_COMMANDES  => 'Toutes les commandes',
        self::DECLENCHEUR_MONTANT_MIN       => 'Commande supérieure ou égale à un montant',
        self::DECLENCHEUR_NOMBRE_COMMANDES  => 'Nombre de commandes atteintes',
    ];

    public const VALIDITE_AUCUNE        = 'aucune';
    public const VALIDITE_DATE_FIXE     = 'date_fixe';
    public const VALIDITE_DUREE_6_MOIS  = 'duree_6_mois';
    public const VALIDITE_DUREE_12_MOIS = 'duree_12_mois';

    public const VALIDITES = [
        self::VALIDITE_AUCUNE        => 'Sans date d\'expiration',
        self::VALIDITE_DATE_FIXE     => 'Date fixe',
        self::VALIDITE_DUREE_6_MOIS  => '6 mois après la commande',
        self::VALIDITE_DUREE_12_MOIS => '12 mois après la commande',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nom;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(length: 30)]
    private string $declencheur = self::DECLENCHEUR_PREMIERE_COMMANDE;

    /** Montant minimum de commande (€) pour le déclencheur montant_min (ou condition complémentaire) */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $montantMinimum = null;

    /** Nombre de commandes payées requis pour le déclencheur nombre_commandes */
    #[ORM\Column(nullable: true)]
    private ?int $nombreCommandes = null;

    /** Plafond global de bénéficiaires (null = illimité) */
    #[ORM\Column(nullable: true)]
    private ?int $maxBeneficiaires = null;

    /** Limiter à un seul bon par adresse email pour cette campagne */
    #[ORM\Column(options: ['default' => true])]
    private bool $uneFoisParClient = true;

    /** 'fixe' | 'pourcentage' */
    #[ORM\Column(length: 20)]
    private string $typeReduction = 'fixe';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $valeur = 5.0;

    /** Arrondir le montant aux 0,50 € supérieurs en mode pourcentage */
    #[ORM\Column(options: ['default' => true])]
    private bool $arrondi50Centimes = true;

    /** Nom fixe du code cadeau (ex. NOEL-5-POURCENT) — null = aléatoire CADEAU-XXXXXXXX */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $codeNom = null;

    /** Date de fin d'activation de la campagne (null = sans fin) */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFinActivation = null;

    /** 'aucune' | 'date_fixe' | 'duree_6_mois' | 'duree_12_mois' */
    #[ORM\Column(length: 20, options: ['default' => 'aucune'])]
    private string $typeValidite = self::VALIDITE_AUCUNE;

    /** Date d'expiration fixe du bon d'achat (si typeValidite = 'date_fixe') */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFinValiditeFixe = null;

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
    public function setNom(string $nom): static { $this->nom = trim($nom); return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }
    public function getDeclencheur(): string { return $this->declencheur; }
    public function setDeclencheur(string $declencheur): static { $this->declencheur = $declencheur; return $this; }
    public function getMontantMinimum(): ?float { return $this->montantMinimum !== null ? (float) $this->montantMinimum : null; }
    public function setMontantMinimum(?float $m): static { $this->montantMinimum = ($m !== null && $m > 0) ? $m : null; return $this; }
    public function getNombreCommandes(): ?int { return $this->nombreCommandes; }
    public function setNombreCommandes(?int $n): static { $this->nombreCommandes = ($n !== null && $n > 0) ? $n : null; return $this; }
    public function getMaxBeneficiaires(): ?int { return $this->maxBeneficiaires; }
    public function setMaxBeneficiaires(?int $max): static { $this->maxBeneficiaires = ($max !== null && $max > 0) ? $max : null; return $this; }
    public function isUneFoisParClient(): bool { return $this->uneFoisParClient; }
    public function setUneFoisParClient(bool $v): static { $this->uneFoisParClient = $v; return $this; }
    public function getTypeReduction(): string { return $this->typeReduction; }
    public function setTypeReduction(string $type): static { $this->typeReduction = $type; return $this; }
    public function getValeur(): float { return (float) $this->valeur; }
    public function setValeur(float $valeur): static { $this->valeur = $valeur; return $this; }
    public function isArrondi50Centimes(): bool { return $this->arrondi50Centimes; }
    public function setArrondi50Centimes(bool $v): static { $this->arrondi50Centimes = $v; return $this; }
    public function getCodeNom(): ?string { return $this->codeNom; }
    public function setCodeNom(?string $code): static { $this->codeNom = ($code !== null && trim($code) !== '') ? strtoupper(trim($code)) : null; return $this; }
    public function getDateFinActivation(): ?\DateTimeImmutable { return $this->dateFinActivation; }
    public function setDateFinActivation(?\DateTimeImmutable $date): static { $this->dateFinActivation = $date; return $this; }
    public function getTypeValidite(): string { return $this->typeValidite; }
    public function setTypeValidite(string $type): static { $this->typeValidite = $type; return $this; }
    public function getDateFinValiditeFixe(): ?\DateTimeImmutable { return $this->dateFinValiditeFixe; }
    public function setDateFinValiditeFixe(?\DateTimeImmutable $date): static { $this->dateFinValiditeFixe = $date; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $date): static { $this->updatedAt = $date; return $this; }

    public function isEnCours(?\DateTimeImmutable $now = null): bool
    {
        if (!$this->actif) {
            return false;
        }

        $now ??= new \DateTimeImmutable();
        if ($this->dateFinActivation !== null && $now > $this->dateFinActivation) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si une commande payée satisfait le déclencheur de cette campagne.
     * $paidOrderCount inclut la commande courante (ex: 1 pour la première commande).
     */
    public function matchesOrder(float $orderTotal, int $paidOrderCount): bool
    {
        return match ($this->declencheur) {
            self::DECLENCHEUR_PREMIERE_COMMANDE => $paidOrderCount === 1,
            self::DECLENCHEUR_TOUTES_COMMANDES  => true,
            self::DECLENCHEUR_MONTANT_MIN       => $this->montantMinimum !== null && $orderTotal >= $this->getMontantMinimum(),
            self::DECLENCHEUR_NOMBRE_COMMANDES  => $this->nombreCommandes !== null && $paidOrderCount >= $this->nombreCommandes,
            default                             => false,
        };
    }

    /**
     * Calcule le montant en euros du bon d'achat pour une commande donnée.
     */
    public function computeAmount(float $orderTotal): float
    {
        if ($this->typeReduction === 'pourcentage') {
            $raw = $orderTotal * $this->getValeur() / 100.0;
            if ($raw <= 0.0) {
                return 0.0;
            }
            if ($this->arrondi50Centimes) {
                return ceil(round($raw * 2, 6)) / 2;
            }
            return round($raw, 2);
        }

        return $this->getValeur();
    }

    /**
     * Calcule la date d'expiration du bon d'achat à émettre.
     */
    public function computeExpirationDate(?\DateTimeImmutable $from = null): ?\DateTimeImmutable
    {
        $from ??= new \DateTimeImmutable();

        return match ($this->typeValidite) {
            self::VALIDITE_DATE_FIXE     => $this->dateFinValiditeFixe,
            self::VALIDITE_DUREE_6_MOIS  => $from->modify('+6 months'),
            self::VALIDITE_DUREE_12_MOIS => $from->modify('+12 months'),
            default                      => null,
        };
    }

    public function getDeclencheurLabel(): string
    {
        return match ($this->declencheur) {
            self::DECLENCHEUR_PREMIERE_COMMANDE => 'Première commande',
            self::DECLENCHEUR_TOUTES_COMMANDES  => 'Toutes les commandes',
            self::DECLENCHEUR_MONTANT_MIN       => sprintf('Commande ≥ %s €', number_format($this->getMontantMinimum() ?? 0, 2, ',', ' ')),
            self::DECLENCHEUR_NOMBRE_COMMANDES  => sprintf('Dès %d commande%s', $this->nombreCommandes ?? 1, ($this->nombreCommandes ?? 1) > 1 ? 's' : ''),
            default                             => $this->declencheur,
        };
    }

    public function getValiditeLabel(): string
    {
        return match ($this->typeValidite) {
            self::VALIDITE_DATE_FIXE     => $this->dateFinValiditeFixe ? 'Jusqu\'au ' . $this->dateFinValiditeFixe->format('d/m/Y') : 'Date fixe',
            self::VALIDITE_DUREE_6_MOIS  => '6 mois après commande',
            self::VALIDITE_DUREE_12_MOIS => '12 mois après commande',
            default                      => 'Illimitée',
        };
    }
}
