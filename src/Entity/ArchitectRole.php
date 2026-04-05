<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ArchitectRole extends Role
{
    public const NAME = 'ROLE_ARCHITECT';

    public function isGamificationRole(): bool
    {
        return true;
    }

    public function getRank(): int
    {
        return 2;
    }
}
