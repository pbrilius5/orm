<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GameMasterRole extends Role
{
    public const NAME = 'ROLE_GAME_MASTER';

    public function isGamificationRole(): bool
    {
        return true;
    }

    public function getRank(): int
    {
        return 3;
    }
}
