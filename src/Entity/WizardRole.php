<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WizardRole extends Role
{
    public const NAME = 'ROLE_WIZARD';

    public function isGamificationRole(): bool
    {
        return true;
    }
}
