<?php

declare(strict_types=1);

namespace App\Command\Group;

class PatchGroupCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
    ) {}
}
