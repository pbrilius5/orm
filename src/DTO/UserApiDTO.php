<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;

class UserApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly ?string $workGroupName,
        public readonly ?string $role,
        public readonly \DateTimeInterface $createdAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getWorkGroupName(): ?string
    {
        return $this->workGroupName;
    }

    public static function fromEntity(
        User $user,
        array $userRoles = [],
        ?\App\Entity\Group $workGroup = null,
        array $gamificationRoles = []
    ): self {
        $roleName = null;
        foreach ($gamificationRoles as $role) {
            $roleName = $role->getName();
            break;
        }

        return new self(
            id: $user->getId()?->toString() ?? '',
            email: $user->getEmail(),
            workGroupName: $workGroup?->getName(),
            role: $roleName,
            createdAt: $user->getCreatedAt(),
        );
    }
}
