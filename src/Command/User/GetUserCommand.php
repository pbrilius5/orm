<?php

declare(strict_types=1);

namespace App\Command\User;

class GetUserCommand
{
    public function __construct(
        public readonly string $id,
    ) {}
}
