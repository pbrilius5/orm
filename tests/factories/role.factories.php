<?php

declare(strict_types=1);

use App\Entity\Role;
use Ramsey\Uuid\Uuid;

$fm->define(Role::class)->setDefinitions([
    'id' => fn() => Uuid::uuid4(),
    'name' => fn() => $fm->random(
        [Role::WIZARD, Role::ARCHITECT, Role::GAME_MASTER]
    ),
    'description' => fn() => 'Magic role for wizard platform',
]);
