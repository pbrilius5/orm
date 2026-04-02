<?php

declare(strict_types=1);

use App\Entity\User;
use App\Entity\Team;

$fm->define(User::class)->setDefinitions([
    'email' => 'user{++}@wizardplatform.com',
    'password' => 'password123',
    'createdAt' => fn() => new \DateTimeImmutable(),
    'updatedAt' => null,
    'team' => 'factory|' . Team::class,
]);
