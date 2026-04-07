<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oryx\ORM\SodiumUuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_roles')]
#[ORM\UniqueConstraint(name: 'user_role_unique', columns: ['user_id'])]
class UserRole
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: SodiumUuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userRoles')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE', onUpdate: 'RESTRICT')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'userRoles')]
    #[ORM\JoinColumn(name: 'role_id', nullable: false, onDelete: 'CASCADE', onUpdate: 'RESTRICT')]
    private Role $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeInterface $grantedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    public function __construct()
    {
        $this->grantedAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
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
