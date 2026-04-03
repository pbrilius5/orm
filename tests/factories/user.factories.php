<?php

declare(strict_types=1);

use App\Entity\User;

$fm->define(User::class)->setDefinitions([
    'email' => 'user{++}@wizardplatform.com',
    'password' => 'password123',
    'createdAt' => fn() => new \DateTimeImmutable(),
    'updatedAt' => null,
]);
