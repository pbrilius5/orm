<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Group;
use App\Service\WorkGroupMapInterface;

final class WorkGroupMap implements WorkGroupMapInterface
{
    /**
     * Central discriminator -> FQCN map for work groups.
     * Keep this single-sourced so other code can rely on the same mapping.
     * Order does not matter here; sorting is done by Group::getRank()
     * when preparing value options.
     *
     * @var array<string,string>
     */
    private const MAP = [
        'developer' => \App\Entity\DeveloperGroup::class,
        'designer' => \App\Entity\DesignerGroup::class,
        'tester' => \App\Entity\TesterGroup::class,
    ];

    /**
     * Return the FQCN for a discriminator or null if unknown.
     */
    public function getFqcnForDiscriminator(string $discriminator): ?string
    {
        return self::MAP[$discriminator] ?? null;
    }

    /**
     * Return the discriminator for a Group instance. If unknown, returns 'group'.
     */
    public function getDiscriminatorForGroup(Group $group): string
    {
        foreach (self::MAP as $disc => $fqcn) {
            if ($group instanceof $fqcn) {
                return $disc;
            }
        }

        return 'group';
    }

    /**
     * All known discriminators keys.
     *
     * @return string[]
     */
    public function getAllDiscriminators(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * Return the entire map.
     *
     * @return array<string,string>
     */
    public function getMap(): array
    {
        return self::MAP;
    }

    // Backwards-compatible static accessors for code that still calls WorkGroupMap::method().
    public static function getFqcnForDiscriminatorStatic(string $discriminator): ?string
    {
        return self::MAP[$discriminator] ?? null;
    }

    public static function getDiscriminatorForGroupStatic(Group $group): string
    {
        foreach (self::MAP as $disc => $fqcn) {
            if ($group instanceof $fqcn) {
                return $disc;
            }
        }

        return 'group';
    }
}
