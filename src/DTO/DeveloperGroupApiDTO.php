<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\DeveloperGroup;

class DeveloperGroupApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $discriminator,
        public readonly int $rank,
        public readonly bool $isWorkGroup,
        public readonly array $users,
        public readonly \DateTimeInterface $createdAt,
    ) {}

    public static function fromEntity(
        DeveloperGroup $group,
        array $users = []
    ): self {
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            discriminator: 'developer',
            rank: $group->getRank(),
            isWorkGroup: $group->isWorkGroup(),
            users: $users,
            createdAt: $group->getCreatedAt(),
        );
    }
}
