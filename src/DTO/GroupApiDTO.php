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
            createdAt: $group->getCreatedAt(),
        );
    }
}
