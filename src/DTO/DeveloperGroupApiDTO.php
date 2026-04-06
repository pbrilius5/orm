<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\DeveloperGroup;

class DeveloperGroupApiDTO extends GroupApiDTO
{
    public static function fromEntity(
        DeveloperGroup $group,
        array $users = [],
        array $gamificationRoles = []
    ): self {
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            discriminator: 'developer',
            rank: $group->getRank(),
            isWorkGroup: $group->isWorkGroup(),
            users: $users,
            gamificationRoles: $gamificationRoles,
            createdAt: $group->getCreatedAt(),
        );
    }
}
