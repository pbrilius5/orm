<?php

declare(strict_types=1);

namespace Oryx\Cache;

use Doctrine\Common\Cache\Cache;
use Oryx\ORM\EntityManager;

class CacheUtility
{
    private ?Cache $cache = null;
    private string $driver = 'none';
    private bool $enabled = false;
    private string $appEnv = 'dev';

    public function __construct(?EntityManager $entityManager = null)
    {
        if ($entityManager !== null) {
            $this->cache = $entityManager->getMetadataCache();
            $this->enabled = $this->cache !== null;

            if ($this->cache !== null) {
                $this->driver = $this->detectDriver();
                // Extract appEnv from entity manager's cache config
                $config = $entityManager->getDoctrineEntityManager()->getConfiguration();
                $secondLevelCacheConfig = $config->getSecondLevelCacheConfiguration();
                if ($secondLevelCacheConfig && isset($secondLevelCacheConfig['regions']['default']['app_env'])) {
                    $this->appEnv = $secondLevelCacheConfig['regions']['default']['app_env'];
                }
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getAppEnv(): string
    {
        return $this->appEnv;
    }

    public function getStats(): array
    {
        if (!$this->enabled || $this->cache === null) {
            return [
                'driver' => 'none',
                'enabled' => false,
                'stats' => null,
            ];
        }

        $stats = $this->cache->getStats();
        $driverInfo = $this->getDriverInfo();

        return [
            'driver' => $this->driver,
            'enabled' => true,
            'app_env' => $this->appEnv,
            'stats' => $stats,
            'driver_info' => $driverInfo,
        ];
    }

    public function get(string $key): mixed
    {
        if (!$this->enabled || $this->cache === null) {
            return null;
        }

        return $this->cache->fetch($key);
    }

    public function has(string $key): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        return $this->cache->contains($key);
    }

    public function delete(string $key): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        if (method_exists($this->cache, 'flush')) {
            return $this->cache->flush();
        }

        // Fallback: delete all known keys if flush is not available
        try {
            $keys = $this->getKeys();
            $success = true;
            foreach ($keys as $key) {
                if (!$this->delete($key)) {
                    $success = false;
                }
            }
            return $success;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getKeys(string $pattern = '*'): array
    {
        if (!$this->enabled || $this->cache === null) {
            return [];
        }

        $stats = $this->cache->getStats();
        if (empty($stats)) {
            return [];
        }

        $keys = [];
        foreach ($stats as $serverKey => $serverStats) {
            if (isset($serverStats['keys'])) {
                foreach ($serverStats['keys'] as $key) {
                    if ($pattern === '*' || fnmatch($pattern, $key, FNM_NOESCAPE)) {
                        $keys[] = $key;
                    }
                }
            }
        }

        return $keys;
    }

    private function detectDriver(): string
    {
        if ($this->cache === null) {
            return 'none';
        }

        $class = get_class($this->cache);

        if (str_contains($class, 'Memcached')) {
            return 'memcached';
        }

        if (str_contains($class, 'Redis')) {
            return 'redis';
        }

        if (str_contains($class, 'Array')) {
            return 'array';
        }

        return 'unknown';
    }

    private function getDriverInfo(): array
    {
        return match ($this->driver) {
            'memcached' => [
                'type' => 'Memcached',
                'description' => 'In-memory key-value store (development)',
                'extension' => 'memcached',
            ],
            'redis' => [
                'type' => 'Redis',
                'description' => 'In-memory data store (production)',
                'extension' => 'redis',
            ],
            'array' => [
                'type' => 'Array',
                'description' => 'In-memory PHP array (no persistence)',
                'extension' => 'none',
            ],
            default => [
                'type' => 'None',
                'description' => 'Cache not configured',
                'extension' => 'none',
            ],
        };
    }
}
