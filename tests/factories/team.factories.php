<?php

declare(strict_types=1);

use App\Entity\Team;

$fm->define(Team::class)->setDefinitions([
    'name' => 'Team {++}',
    'description' => fn() => 'Demo team created by faker',
    'createdAt' => fn() => new \DateTimeImmutable(),
]);
