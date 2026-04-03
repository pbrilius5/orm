<?php

declare(strict_types=1);

namespace App\Command\Console;

class ManageUserCommand
{
    public const ACTION_SHOW = 'show';
    public const ACTION_ENABLE = 'enable';
    public const ACTION_DISABLE = 'disable';
    public const ACTION_ASSIGN_ROLE = 'assign-role';
    public const ACTION_REMOVE_ROLE = 'remove-role';
    public const ACTION_CHANGE_GROUP = 'change-group';

    public function __construct(
        public readonly string $id,
        public readonly string $action,
        public readonly ?string $role = null,
        public readonly ?string $groupId = null,
        public readonly bool $log = false,
    ) {}
}
