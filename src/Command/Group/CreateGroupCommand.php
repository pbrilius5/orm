<?php

declare(strict_types=1);

namespace App\Command\Group;

class CreateGroupCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}
}
