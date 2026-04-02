<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class UserRole
{
    private ?int $id = null;
    private User $user;
    private Role $role;
    private Team $team;
    private \DateTimeInterface $grantedAt;
    private ?\DateTimeInterface $expiresAt = null;

    public function __construct()
    {
        $this->grantedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    public function getGrantedAt(): \DateTimeInterface
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(\DateTimeInterface $grantedAt): self
    {
        $this->grantedAt = $grantedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}
