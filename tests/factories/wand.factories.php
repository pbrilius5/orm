<?php

declare(strict_types=1);

use App\Entity\Wand;
use App\Entity\User;
use App\Entity\Role;

$fm->define(Wand::class)->setDefinitions([
    'user' => 'factory|' . User::class,
    'role' => 'factory|' . Role::class,
    'name' => fn() => 'Wand of ' . ucfirst($fm->random(['Power', 'Protection', 'Creation', 'Destruction'])),
    'permissions' => fn() => json_encode($fm->random([
        ['read', 'write'],
        ['read', 'write', 'delete'],
        ['admin', 'manage'],
        ['create_levels', 'edit_levels'],
    ])),
    'createdAt' => fn() => new \DateTimeImmutable(),
    'expiresAt' => null,
]);
