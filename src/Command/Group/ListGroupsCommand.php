<?php

declare(strict_types=1);

namespace App\Command\Group;

class ListGroupsCommand
{
    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        public readonly ?string $search = null,
    ) {}
}
