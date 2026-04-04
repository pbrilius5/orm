<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Role;

class RoleApiDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isGamificationRole,
        public readonly string $discriminator,
    ) {}

    public static function fromEntity(Role $role): self
    {
        return new self(
            id: $role->getId()?->toString() ?? '',
            name: $role->getName(),
            description: $role->getDescription(),
            isGamificationRole: $role->isGamificationRole(),
            discriminator: 'role',
        );
    }
}
