<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Oryx\Cache\CacheUtility;

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
        // Test with mock EntityManager that returns null cache
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);

        $this->assertFalse($utility->isEnabled());
        $this->assertEquals('none', $utility->getDriver());
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

    public function testGetReturnsNullWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $this->assertNull($utility->get('any_key'));
    }

    public function testHasReturnsFalseWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $this->assertFalse($utility->has('any_key'));
    }

    public function testDeleteReturnsFalseWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $this->assertFalse($utility->delete('any_key'));
    }

    public function testClearReturnsFalseWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $this->assertFalse($utility->clear());
    }

    public function testGetKeysReturnsEmptyWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $this->assertEmpty($utility->getKeys());
    }

    public function testGetKeysReturnsEmptyArrayWithPatternWhenDisabled(): void
    {
        $mockEm = $this->createMock(\Oryx\ORM\EntityManager::class);
        $mockEm->method('getMetadataCache')->willReturn(null);

        $utility = new CacheUtility($mockEm);
        $this->assertEmpty($utility->getKeys('App*'));
    }
}
