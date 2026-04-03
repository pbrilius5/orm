<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User
{
    private ?int $id = null;
    private string $email;
    private string $password;
    private \DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt = null;
    private Collection $posts;
    private Collection $userRoles;
    private Collection $wands;
    private Collection $patronuses;
    private Collection $invisibilityCloaks;
    private ?Team $team = null;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->userRoles = new ArrayCollection();
        $this->wands = new ArrayCollection();
        $this->patronuses = new ArrayCollection();
        $this->invisibilityCloaks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }
        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        if ($team !== null && !$team->getUsers()->contains($this)) {
            $team->addUser($this);
        }
        return $this;
    }

    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function hasRole(string $roleName, ?Team $team = null): bool
    {
        foreach ($this->userRoles as $userRole) {
            if ($userRole->getRole()->getName() === $roleName && $userRole->isActive()) {
                if ($team === null || $userRole->getTeam() === $team) {
                    return true;
                }
            }
        }
        return false;
    }

    public function addRole(Role $role, Team $team): self
    {
        foreach ($this->userRoles as $existingUserRole) {
            if ($existingUserRole->getRole() === $role && $existingUserRole->getTeam() === $team) {
                return $this;
            }
        }

        $userRole = new UserRole();
        $userRole->setUser($this);
        $userRole->setRole($role);
        $userRole->setTeam($team);
        $this->userRoles->add($userRole);
        $role->addUserRole($userRole);

        if ($role->getName() !== Role::WIZARD && !$this->hasRole(Role::WIZARD, $team)) {
            $wizardRole = new Role();
            $wizardRole->setName(Role::WIZARD);
            $wizardRole->setTeam($team);
            $this->addRole($wizardRole, $team);
        }

        return $this;
    }

    public function setRoles(array $roleNames): self
    {
        $this->userRoles->clear();

        foreach ($roleNames as $roleName) {
            $role = new Role();
            $role->setName($roleName);

            $userRole = new UserRole();
            $userRole->setUser($this);
            $userRole->setRole($role);

            $team = $this->getTeam();
            if ($team !== null) {
                $userRole->setTeam($team);
            }

            $userRole->setGrantedAt(new \DateTimeImmutable());
            $this->userRoles->add($userRole);
        }

        return $this;
    }

    public function removeRole(Role $role, Team $team): self
    {
        foreach ($this->userRoles as $userRole) {
            if ($userRole->getRole() === $role && $userRole->getTeam() === $team) {
                $this->userRoles->removeElement($userRole);
                $role->removeUserRole($userRole);
                break;
            }
        }
        return $this;
    }

    public function getRolesForTeam(Team $team): array
    {
        return array_values($this->userRoles
            ->filter(fn($ur) => $ur->getTeam() === $team && $ur->isActive())
            ->map(fn($ur) => $ur->getRole())
            ->toArray());
    }

    public function getAllRoles(): array
    {
        return array_values($this->userRoles
            ->filter(fn($ur) => $ur->isActive())
            ->map(fn($ur) => $ur->getRole())
            ->toArray());
    }

    public function getRoleNames(): array
    {
        return array_values($this->userRoles
            ->filter(fn($ur) => $ur->isActive())
            ->map(fn($ur) => $ur->getRole()->getName())
            ->toArray());
    }

    public function getRoles(): array
    {
        return $this->getRoleNames();
    }

    public function addWand(Wand $wand): self
    {
        if (!$this->wands->contains($wand)) {
            $this->wands->add($wand);
            $wand->setUser($this);
        }
        return $this;
    }

    public function removeWand(Wand $wand): self
    {
        if ($this->wands->contains($wand)) {
            $this->wands->removeElement($wand);
            if ($wand->getUser() === $this) {
                $wand->setUser(null);
            }
        }
        return $this;
    }

    public function getWands(): Collection
    {
        return $this->wands;
    }

    public function getExpiredWands(): array
    {
        return array_values($this->wands
            ->filter(fn($wand) => $wand->isExpired())
            ->toArray());
    }

    public function getActiveWands(): array
    {
        return array_values($this->wands
            ->filter(fn($wand) => !$wand->isExpired())
            ->toArray());
    }

    public function addPatronus(Patronus $patronus): self
    {
        if (!$this->patronuses->contains($patronus)) {
            $this->patronuses->add($patronus);
            $patronus->setUser($this);
        }
        return $this;
    }

    public function removePatronus(Patronus $patronus): self
    {
        if ($this->patronuses->contains($patronus)) {
            $this->patronuses->removeElement($patronus);
            if ($patronus->getUser() === $this) {
                $patronus->setUser(null);
            }
        }
        return $this;
    }

    public function getPatronuses(): Collection
    {
        return $this->patronuses;
    }

    public function getValidPatronuses(): array
    {
        return array_values($this->patronuses
            ->filter(fn($patronus) => $patronus->isValid())
            ->toArray());
    }

    public function addInvisibilityCloak(InvisibilityCloak $cloak): self
    {
        if (!$this->invisibilityCloaks->contains($cloak)) {
            $this->invisibilityCloaks->add($cloak);
            $cloak->setUser($this);
        }
        return $this;
    }

    public function removeInvisibilityCloak(InvisibilityCloak $cloak): self
    {
        if ($this->invisibilityCloaks->contains($cloak)) {
            $this->invisibilityCloaks->removeElement($cloak);
            if ($cloak->getUser() === $this) {
                $cloak->setUser(null);
            }
        }
        return $this;
    }

    public function getInvisibilityCloaks(): Collection
    {
        return $this->invisibilityCloaks;
    }

    public function getActiveInvisibilityCloaks(): array
    {
        return array_values($this->invisibilityCloaks
            ->filter(fn($cloak) => $cloak->isActive())
            ->toArray());
    }

    public function isInvisibleInTeam(Team $team): bool
    {
        foreach ($this->invisibilityCloaks as $cloak) {
            if ($cloak->getTeam() === $team && $cloak->isActive()) {
                return true;
            }
        }
        return false;
    }

    public function hasValidPatronusInTeam(Team $team): bool
    {
        foreach ($this->patronuses as $patronus) {
            if ($patronus->getTeam() === $team && $patronus->isValid()) {
                return true;
            }
        }
        return false;
    }

    public function hasWandPermission(string $permission): bool
    {
        foreach ($this->wands as $wand) {
            if ($wand->hasPermission($permission) && !$wand->isExpired()) {
                return true;
            }
        }
        return false;
    }
}
