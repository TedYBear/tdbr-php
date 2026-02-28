<?php

namespace App\Entity;

use App\Repository\SiteConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteConfigRepository::class)]
#[ORM\Table(name: 'site_config')]
class SiteConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $bannerActive = true;

    #[ORM\Column(length: 100)]
    private string $bannerTitre = 'Site en construction';

    #[ORM\Column(length: 500)]
    private string $bannerTexte = 'Les articles et prix sont réels — la commande en ligne n\'est pas encore disponible.';

    public function getId(): ?int { return $this->id; }

    public function isBannerActive(): bool { return $this->bannerActive; }
    public function setBannerActive(bool $v): static { $this->bannerActive = $v; return $this; }

    public function getBannerTitre(): string { return $this->bannerTitre; }
    public function setBannerTitre(string $v): static { $this->bannerTitre = $v; return $this; }

    public function getBannerTexte(): string { return $this->bannerTexte; }
    public function setBannerTexte(string $v): static { $this->bannerTexte = $v; return $this; }
}
