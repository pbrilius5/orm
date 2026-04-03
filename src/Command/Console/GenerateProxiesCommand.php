<?php

declare(strict_types=1);

namespace App\Command\Console;

class GenerateProxiesCommand
{
    public function __construct(
        public readonly bool $log = false,
    ) {}
}
