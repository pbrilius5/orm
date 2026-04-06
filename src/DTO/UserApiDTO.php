<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;

class UserApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly ?string $workGroupId,
        public readonly ?string $workGroupName,
        public readonly int $workGroupRank,
        public readonly array $gamificationRoles,
        public readonly ?string $highestRankRole,
        public readonly int $highestRankRoleValue,
        public readonly array $userRoles,
        public readonly array $groups,
        public readonly \DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getGamificationRoles(): array
    {
        return $this->gamificationRoles;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public static function fromEntity(
        User $user,
        array $userRoles = [],
        ?\App\Entity\Group $workGroup = null,
        array $gamificationRoles = []
    ): self {
        $roles = [];
        foreach ($userRoles as $ur) {
            if ($ur->isActive()) {
                $roles[] = $ur->getRole()?->getName();
            }
        }

        $highestRole = null;
        $highestRank = 0;
        $gamificationRoleNames = [];
        foreach ($gamificationRoles as $role) {
            $gamificationRoleNames[] = $role->getName();
            if ($role->getRank() > $highestRank) {
                $highestRank = $role->getRank();
                $highestRole = $role->getName();
            }
        }

        return new self(
            id: $user->getId()?->toString() ?? '',
            email: $user->getEmail(),
            workGroupId: $workGroup?->getId()?->toString(),
            workGroupName: $workGroup?->getName(),
            workGroupRank: $workGroup?->getRank() ?? 0,
            gamificationRoles: $gamificationRoleNames,
            highestRankRole: $highestRole,
            highestRankRoleValue: $highestRank,
            userRoles: $roles,
            groups: array_map(fn($g) => $g->getName(), $user->getAllGroups()),
            createdAt: $user->getCreatedAt(),
            updatedAt: $user->getUpdatedAt(),
        );
    }
}
