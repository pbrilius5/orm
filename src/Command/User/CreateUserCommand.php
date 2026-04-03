<?php

declare(strict_types=1);

namespace App\Command\User;

class CreateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $groupId = null,
        public readonly array $roles = [],
    ) {}
}
