<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\ArchitectRole;

class ArchitectRoleApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $discriminator,
    ) {}

    public static function fromEntity(ArchitectRole $role): self
    {
        return new self(
            id: $role->getId()?->toString() ?? '',
            name: $role->getName(),
            description: $role->getDescription(),
            discriminator: 'architect',
        );
    }
}
