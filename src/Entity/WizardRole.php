<?php

declare(strict_types=1);

namespace App\Entity;

class WizardRole extends Role
{
    public const NAME = 'ROLE_WIZARD';

    public function isGamificationRole(): bool
    {
        return true;
    }
}
