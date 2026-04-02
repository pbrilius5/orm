<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Team
{
    private ?int $id = null;
    private string $name;
    private ?string $description = null;
    private \DateTimeInterface $createdAt;
    private Collection $users;
    private Collection $roles;
    private Collection $invisibilityCloaks;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->invisibilityCloaks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);
        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->setTeam($this);
        }
        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            if ($role->getTeam() === $this) {
                $role->setTeam(null);
            }
        }
        return $this;
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addInvisibilityCloak(InvisibilityCloak $cloak): self
    {
        if (!$this->invisibilityCloaks->contains($cloak)) {
            $this->invisibilityCloaks->add($cloak);
            $cloak->setTeam($this);
        }
        return $this;
    }

    public function removeInvisibilityCloak(InvisibilityCloak $cloak): self
    {
        if ($this->invisibilityCloaks->contains($cloak)) {
            $this->invisibilityCloaks->removeElement($cloak);
            if ($cloak->getTeam() === $this) {
                $cloak->setTeam(null);
            }
        }
        return $this;
    }

    public function getInvisibilityCloaks(): Collection
    {
        return $this->invisibilityCloaks;
    }
}
