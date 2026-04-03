<?php

declare(strict_types=1);

namespace App\Command\Group;

class DeleteGroupCommand
{
    public function __construct(
        public readonly string $id,
    ) {}
}
