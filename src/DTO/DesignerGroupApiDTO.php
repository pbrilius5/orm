<?php

declare(strict_types=1);

namespace App\DTO;

class DesignerGroupApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $users,
        public readonly \DateTimeInterface $createdAt,
    ) {}

    public static function fromEntity(
        \App\Entity\DesignerGroup $group,
        array $users = []
    ): self {
        return new self(
            id: $group->getId()?->toString() ?? '',
            name: $group->getName(),
            description: $group->getDescription(),
            users: $users,
            createdAt: $group->getCreatedAt(),
        );
    }
}
