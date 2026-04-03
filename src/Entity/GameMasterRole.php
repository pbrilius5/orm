<?php

declare(strict_types=1);

namespace App\Entity;

class GameMasterRole extends Role
{
    public const NAME = 'ROLE_GAME_MASTER';

    public function isGamificationRole(): bool
    {
        return true;
    }
}
