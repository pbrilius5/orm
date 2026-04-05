<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DesignerGroup extends Group
{
    public function getRank(): int
    {
        return 2;
    }

    public function isWorkGroup(): bool
    {
        return true;
    }
}
