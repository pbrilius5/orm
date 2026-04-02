<?php

declare(strict_types=1);

use App\Entity\UserRole;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\Team;

$fm->define(UserRole::class)->setDefinitions([
    'user' => 'factory|' . User::class,
    'role' => 'factory|' . Role::class,
    'team' => 'factory|' . Team::class,
    'grantedAt' => fn() => new \DateTimeImmutable(),
    'expiresAt' => null,
]);
