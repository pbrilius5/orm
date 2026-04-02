<?php

declare(strict_types=1);

use App\Entity\Role;

$fm->define(Role::class)->setDefinitions([
    'name' => fn() => $fm->random(
        [Role::WIZARD, Role::ARCHITECT, Role::GAME_MASTER]
    ),
    'description' => fn() => 'Magic role for wizard platform',
]);
