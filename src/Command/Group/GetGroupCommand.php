<?php

declare(strict_types=1);

namespace App\Command\Group;

class GetGroupCommand
{
    public function __construct(
        public readonly string $id,
    ) {}
}
