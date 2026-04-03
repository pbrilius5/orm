<?php

declare(strict_types=1);

namespace App\Entity;

class ArchitectRole extends Role
{
    public const NAME = 'ROLE_ARCHITECT';

    public function isGamificationRole(): bool
    {
        return true;
    }
}
