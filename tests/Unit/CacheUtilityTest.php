<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Oryx\Cache\CacheUtility;
use Psr\SimpleCache\CacheInterface as Psr16;

class SimpleArrayPsr16 implements Psr16
{
    private array $store = [];

    public function get($key, $default = null)
    {
        return $this->store[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function delete($key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $k) {
            $result[$k] = $this->get($k, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $k) {
            $this->delete($k);
        }
        return true;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->store);
    }
}

class CacheUtilityTest extends TestCase
{
    public function testCacheUtilityDisabledWhenNoEntityManager(): void
    {
        $utility = new CacheUtility(null);

        $this->assertFalse($utility->isEnabled());
        $this->assertEquals('none', $utility->getDriver());
    }

    public function testCacheUtilityDetectsDriverFromNullCache(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);

        $this->assertFalse($utility->isEnabled());
        $this->assertEquals('none', $utility->getDriver());
    }

    public function testCacheUtilityWorksWithPsr16(): void
    {
        $psr = new SimpleArrayPsr16();
        $psr->set('k', 'v');

        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn($psr);

        $utility = new CacheUtility($mockEm);

        $this->assertTrue($utility->isEnabled());
        $this->assertEquals('psr16', $utility->getDriver());
        $this->assertEquals('v', $utility->get('k'));
        $this->assertTrue($utility->has('k'));
        $this->assertTrue($utility->delete('k'));
        $this->assertNull($utility->get('k'));
    }

    public function testGetStatsReturnsCorrectStructureWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $stats = $utility->getStats();

        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('stats', $stats);
        $this->assertFalse($stats['enabled']);
        $this->assertEquals('none', $stats['driver']);
    }
}
