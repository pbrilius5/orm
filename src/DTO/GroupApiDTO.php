<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Group;

class GroupApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $discriminator,
        public readonly int $rank,
        public readonly bool $isWorkGroup,
        public readonly array $users,
        public readonly array $gamificationRoles,
        public readonly \DateTimeInterface $createdAt,
    ) {}

    public static function fromEntity(
        Group $group,
        array $users = [],
        array $gamificationRoles = []
    ): self {
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            discriminator: 'group',
            rank: $group->getRank(),
            isWorkGroup: $group->isWorkGroup(),
            users: $users,
            gamificationRoles: $gamificationRoles,
            createdAt: $group->getCreatedAt(),
        );
    }
}
