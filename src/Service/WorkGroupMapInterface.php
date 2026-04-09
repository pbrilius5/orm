<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Group;

interface WorkGroupMapInterface
{
    public function getFqcnForDiscriminator(string $discriminator): ?string;

    public function getDiscriminatorForGroup(Group $group): string;

    /**
     * @return string[]
     */
    public function getAllDiscriminators(): array;

    /**
     * @return array<string,string>
     */
    public function getMap(): array;
}
