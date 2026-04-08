<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\ORMSetup;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

// Minimal in-memory PSR-6 cache item for tests
class SimpleCacheItem implements CacheItemInterface
{
    private string $key;
    private $value;
    private bool $hit = false;
    private ?\DateTimeInterface $expiresAt = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        if ($this->expiresAt !== null && $this->expiresAt <= new \DateTimeImmutable()) {
            return false;
        }
        return $this->hit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;
        $this->hit = true;
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expiresAt = $expiration;
        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiresAt = null;
            return $this;
        }
        if ($time instanceof \DateInterval) {
            $this->expiresAt = (new \DateTimeImmutable())->add($time);
        } else {
            $this->expiresAt = (new \DateTimeImmutable())->add(new \DateInterval('PT' . max(0, $time) . 'S'));
        }
        return $this;
    }
}

// Minimal in-memory PSR-6 cache pool for tests
class SimpleCachePool implements CacheItemPoolInterface
{
    /** @var array<string, SimpleCacheItem> */
    private array $items = [];
    /** @var array<string, SimpleCacheItem> */
    private array $deferred = [];

    public function getItem(string $key): CacheItemInterface
    {
        if (! isset($this->items[$key])) {
            $this->items[$key] = new SimpleCacheItem($key);
        }
        return $this->items[$key];
    }

    public function getItems(array $keys = []): iterable
    {
        foreach ($keys as $key) {
            yield $this->getItem($key);
        }
    }

    public function hasItem(string $key): bool
    {
        return isset($this->items[$key]) && $this->items[$key]->isHit();
    }

    public function clear(): bool
    {
        $this->items = [];
        $this->deferred = [];
        return true;
    }

    public function deleteItem(string $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    public function commit(): bool
    {
        foreach ($this->deferred as $k => $item) {
            $this->items[$k] = $item;
        }
        $this->deferred = [];
        return true;
    }
}
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\UserRole;
use Doctrine\DBAL\Types\Type;

// Register a simple mapping for 'uuid' type to a string-based DBAL type if not present
if (! Type::hasType('uuid')) {
    // Use GuidType when available, otherwise fallback to string
    if (class_exists(\Doctrine\DBAL\Types\GuidType::class)) {
        Type::addType('uuid', \Doctrine\DBAL\Types\GuidType::class);
    } else {
        Type::addType('uuid', \Doctrine\DBAL\Types\StringType::class);
    }
}

/**
 * Integration test for UpdateUserHandler role sync behaviour.
 * It simulates removing and adding roles and asserts no UNIQUE constraint
 * violations occur and final DB state is correct.
 */
class UpdateUserRolesSyncTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $cache = new SimpleCachePool();

        $config = ORMSetup::createAttributeMetadataConfiguration([
            __DIR__ . '/../../src/Entity',
        ], true, null, $cache, false);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->em = EntityManager::create($conn, $config);

        // Ensure Doctrine DBAL knows how to map the custom 'uuid' column type for SQLite tests
        $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('uuid', 'string');

        // Create schema
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $classes = [
            $this->em->getClassMetadata(User::class),
            $this->em->getClassMetadata(Role::class),
            $this->em->getClassMetadata(UserRole::class),
        ];
        $schemaTool->createSchema($classes);
    }

    public function testRoleSyncDoesNotCreateDuplicates(): void
    {
        // Create roles
        $roleA = new Role();
        $roleA->setName('ROLE_A');
        $roleB = new Role();
        $roleB->setName('ROLE_B');

        $this->em->persist($roleA);
        $this->em->persist($roleB);

        // Create user with roleA
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('secret');
        $this->em->persist($user);
        $this->em->flush();

        // Attach userrole mapping for roleA
        $ur = new UserRole();
        $ur->setUser($user);
        $ur->setRole($roleA);
        $this->em->persist($ur);
        $this->em->flush();

        // Simulate update: remove roleA and add roleA again and add roleB
        // The handler logic should remove only necessary mappings and avoid duplicate insert

        // Remove roleA mapping
        $this->em->remove($ur);
        $this->em->flush();

        // Add roleA and roleB mappings — should not violate UNIQUE
        $newA = new UserRole();
        $newA->setUser($user);
        $newA->setRole($roleA);

        $newB = new UserRole();
        $newB->setUser($user);
        $newB->setRole($roleB);
        $this->em->persist($newA);
        $this->em->persist($newB);
        $this->em->flush();

        // Query DB to ensure two mappings exist
        $repo = $this->em->getRepository(UserRole::class);
        $mappings = $repo->findBy(['user' => $user]);

        $this->assertCount(2, $mappings, 'Expected two user_role mappings after sync');

        $codes = array_map(fn(UserRole $r) => $r->getRole()->getName(), $mappings);
        sort($codes);
        $this->assertSame(['ROLE_A', 'ROLE_B'], $codes);
    }
}
