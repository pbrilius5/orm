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
    private ?Team $team = null;
    private Collection $userRoles;
    private Collection $wands;
    private Collection $patronuses;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->wands = new ArrayCollection();
        $this->patronuses = new ArrayCollection();
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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    public function hasScope(?Team $team): bool
    {
        if ($this->team === null) {
            return true;
        }
        return $team === null || $this->team === $team;
    }

    public function isGlobal(): bool
    {
        return $this->team === null;
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

    public function addWand(Wand $wand): self
    {
        if (!$this->wands->contains($wand)) {
            $this->wands->add($wand);
            $wand->setRole($this);
        }
        return $this;
    }

    public function removeWand(Wand $wand): self
    {
        if ($this->wands->contains($wand)) {
            $this->wands->removeElement($wand);
            if ($wand->getRole() === $this) {
                $wand->setRole(null);
            }
        }
        return $this;
    }

    public function getWands(): Collection
    {
        return $this->wands;
    }

    public function addPatronus(Patronus $patronus): self
    {
        if (!$this->patronuses->contains($patronus)) {
            $this->patronuses->add($patronus);
            $patronus->setRole($this);
        }
        return $this;
    }

    public function removePatronus(Patronus $patronus): self
    {
        if ($this->patronuses->contains($patronus)) {
            $this->patronuses->removeElement($patronus);
            if ($patronus->getRole() === $this) {
                $patronus->setRole(null);
            }
        }
        return $this;
    }

    public function getPatronuses(): Collection
    {
        return $this->patronuses;
    }
}
