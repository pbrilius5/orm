<?php

declare(strict_types=1);

namespace App\Command\Group;

class UpdateGroupCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}
}
