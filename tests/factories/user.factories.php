<?php

declare(strict_types=1);

use App\Entity\User;
use Ramsey\Uuid\Uuid;

$fm->define(User::class)->setDefinitions([
    'id' => fn() => Uuid::uuid4(),
    'email' => 'user{++}@wizardplatform.com',
    'password' => 'password123',
    'createdAt' => fn() => new \DateTimeImmutable(),
    'updatedAt' => null,
]);
