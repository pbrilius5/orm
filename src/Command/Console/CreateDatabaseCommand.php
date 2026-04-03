<?php

declare(strict_types=1);

namespace App\Command\Console;

class CreateDatabaseCommand
{
    public function __construct(
        public readonly bool $force = false,
        public readonly bool $log = false,
    ) {}
}
