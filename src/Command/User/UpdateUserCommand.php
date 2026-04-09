<?php

declare(strict_types=1);

namespace App\Command\User;

class UpdateUserCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $workGroup = null,
        public readonly array $roles = [],
    ) {}
}
