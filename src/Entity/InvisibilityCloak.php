<?php

declare(strict_types=1);

namespace App\Entity;

class InvisibilityCloak
{
    private ?int $id = null;
    private User $user;
    private Team $team;
    private \DateTimeInterface $grantedAt;
    private ?\DateTimeInterface $expiresAt = null;
    private bool $isActive = true;

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

    public function isActive(): bool
    {
        if (!$this->isActive) {
            return false;
        }
        if ($this->expiresAt === null) {
            return true;
        }
        return $this->expiresAt >= new \DateTimeImmutable();
    }

    public function deactivate(): self
    {
        $this->isActive = false;
        return $this;
    }

    public function activate(): self
    {
        $this->isActive = true;
        return $this;
    }
}
