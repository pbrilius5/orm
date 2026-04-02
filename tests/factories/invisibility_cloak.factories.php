<?php

declare(strict_types=1);

use App\Entity\InvisibilityCloak;
use App\Entity\User;
use App\Entity\Team;

$fm->define(InvisibilityCloak::class)->setDefinitions([
    'user' => 'factory|' . User::class,
    'team' => 'factory|' . Team::class,
    'grantedAt' => fn() => new \DateTimeImmutable(),
    'expiresAt' => null,
    'isActive' => true,
]);
