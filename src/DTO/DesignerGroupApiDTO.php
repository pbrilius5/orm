<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\DesignerGroup;
use App\Entity\Group;

class DesignerGroupApiDTO extends GroupApiDTO
{
    public static function fromEntity(
        Group $group,
        array $users = [],
        array $gamificationRoles = []
    ): self {
        assert($group instanceof DesignerGroup);
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            discriminator: 'designer',
            rank: $group->getRank(),
            isWorkGroup: $group->isWorkGroup(),
            users: $users,
            gamificationRoles: $gamificationRoles,
            createdAt: $group->getCreatedAt(),
        );
    }
}
