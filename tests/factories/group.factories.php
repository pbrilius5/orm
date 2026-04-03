<?php

declare(strict_types=1);

use App\Entity\Group;
use Ramsey\Uuid\Uuid;

$fm->define(Group::class)->setDefinitions([
    'id' => fn() => Uuid::uuid4(),
    'name' => 'Group {++}',
    'createdAt' => fn() => new \DateTimeImmutable(),
]);
