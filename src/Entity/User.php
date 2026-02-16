<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $id;
    private string $email;
    private string $password;
    private array $roles;
    private ?string $prenom = null;
    private ?string $nom = null;
    private ?string $telephone = null;

    public function __construct(
        string $id,
        string $email,
        string $password,
        array $roles = ['ROLE_USER'],
        ?string $prenom = null,
        ?string $nom = null,
        ?string $telephone = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
        $this->prenom = $prenom;
        $this->nom = $nom;
        $this->telephone = $telephone;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Nothing to do here
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getFullName(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }
}
