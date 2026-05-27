<?php

namespace App\Entity;

use App\Repository\PageViewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageViewRepository::class)]
#[ORM\Table(name: 'page_views')]
#[ORM\Index(columns: ['viewed_at'], name: 'idx_page_views_viewed_at')]
#[ORM\Index(columns: ['entity_type', 'entity_slug'], name: 'idx_page_views_entity')]
#[ORM\Index(columns: ['route_name'], name: 'idx_page_views_route')]
class PageView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $routeName;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $entitySlug = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(length: 64)]
    private string $sessionId;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ipPartial = null;

    #[ORM\Column]
    private \DateTimeImmutable $viewedAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->viewedAt = $now;
        $this->createdAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): static
    {
        $this->routeName = $routeName;
        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntitySlug(): ?string
    {
        return $this->entitySlug;
    }

    public function setEntitySlug(?string $entitySlug): static
    {
        $this->entitySlug = $entitySlug;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getIpPartial(): ?string
    {
        return $this->ipPartial;
    }

    public function setIpPartial(?string $ipPartial): static
    {
        $this->ipPartial = $ipPartial;
        return $this;
    }

    public function getViewedAt(): \DateTimeImmutable
    {
        return $this->viewedAt;
    }

    public function setViewedAt(\DateTimeImmutable $viewedAt): static
    {
        $this->viewedAt = $viewedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
