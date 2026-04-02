<?php

declare(strict_types=1);

use App\Entity\Patronus;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\Team;

$fm->define(Patronus::class)->setDefinitions([
    'user' => 'factory|' . User::class,
    'role' => 'factory|' . Role::class,
    'team' => 'factory|' . Team::class,
    'token' => fn() => bin2hex(random_bytes(32)),
    'issuedAt' => fn() => new \DateTimeImmutable(),
    'expiresAt' => fn() => new \DateTimeImmutable('+24 hours'),
]);
