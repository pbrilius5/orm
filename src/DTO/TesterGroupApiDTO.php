<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Group;
use App\Entity\TesterGroup;

class TesterGroupApiDTO extends GroupApiDTO
{
    public static function fromEntity(
        Group $group,
        array $users = [],
        array $gamificationRoles = []
    ): self {
        assert($group instanceof TesterGroup);
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            discriminator: 'tester',
            rank: $group->getRank(),
            isWorkGroup: $group->isWorkGroup(),
            users: $users,
            gamificationRoles: $gamificationRoles,
            createdAt: $group->getCreatedAt(),
        );
    }
}
