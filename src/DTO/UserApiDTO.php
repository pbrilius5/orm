<?php

declare(strict_types=1);

namespace App\DTO;

class UserApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly array $roles,
        public readonly ?string $groupId,
        public readonly ?string $groupName,
        public readonly array $userRoles,
        public readonly \DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
    ) {}

    public static function fromEntity(
        \App\Entity\User $user,
        array $userRoles = [],
        ?\App\Entity\Group $group = null
    ): self {
        $roles = [];
        foreach ($userRoles as $ur) {
            if ($ur->isActive()) {
                $roles[] = $ur->getRole()?->getName();
            }
        }

        return new self(
            id: $user->getId()?->toString() ?? '',
            email: $user->getEmail(),
            roles: $roles,
            groupId: $group?->getId()?->toString(),
            groupName: $group?->getName(),
            userRoles: $userRoles,
            createdAt: $user->getCreatedAt(),
            updatedAt: $user->getUpdatedAt(),
        );
    }
}
