<?php

declare(strict_types=1);

namespace Oryx\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * UUID generator using Ramsey UUID v4.
 *
 * Uses random_bytes() internally, which prefers libsodium (ext-sodium)
 * when available for cryptographically secure random bytes.
 */
class SodiumUuidGenerator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, $entity): UuidInterface
    {
        return Uuid::uuid4();
    }
}
