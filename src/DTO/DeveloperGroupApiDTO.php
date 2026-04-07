<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\DeveloperGroup;
use App\Entity\Group;

class DeveloperGroupApiDTO extends GroupApiDTO
{
    public static function fromEntity(
        Group $group,
        array $users = [],
        array $gamificationRoles = []
    ): self {
        assert($group instanceof DeveloperGroup);
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            createdAt: $group->getCreatedAt(),
        );
    }
}
