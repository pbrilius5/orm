<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Role
{
    public const WIZARD = 'ROLE_WIZARD';
    public const ARCHITECT = 'ROLE_ARCHITECT';
    public const GAME_MASTER = 'ROLE_GAME_MASTER';
    public const MUGGLE = 'ROLE_MUGGLE';
    public const USER = 'ROLE_USER';

    private ?int $id = null;
    private string $name;
    private ?string $description = null;
    private Collection $userRoles;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isGlobal(): bool
    {
        return true;
    }

    public function addUserRole(UserRole $userRole): self
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles->add($userRole);
        }
        return $this;
    }

    public function removeUserRole(UserRole $userRole): self
    {
        $this->userRoles->removeElement($userRole);
        return $this;
    }

    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }
}
