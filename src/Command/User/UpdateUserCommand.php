<?php

declare(strict_types=1);

namespace App\Command\User;

class UpdateUserCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $workGroup = null,
        public readonly array $roles = [],
    ) {}
}
