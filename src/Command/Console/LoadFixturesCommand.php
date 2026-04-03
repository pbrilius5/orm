<?php

declare(strict_types=1);

namespace App\Command\Console;

class LoadFixturesCommand
{
    public function __construct(
        public readonly int $groups = 3,
        public readonly int $users = 10,
        public readonly bool $purge = false,
        public readonly ?int $seed = null,
        public readonly bool $log = false,
    ) {}
}
