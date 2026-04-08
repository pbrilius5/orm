<?php

declare(strict_types=1);

namespace Oryx\Cache;

use Oryx\ORM\EntityManager;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;
use Psr\Cache\CacheItemPoolInterface as Psr6CacheInterface;

class CacheUtility
{
    // No longer relies on Doctrine\Common\Cache; keep minimal state for compatibility
    private ?object $cache = null;
    private string $driver = 'none';
    private bool $enabled = false;
    private string $appEnv = 'dev';

    public function __construct(?EntityManager $entityManager = null)
    {
        // Do not attempt to use Doctrine's deprecated cache API. EntityManager no longer
        // provides a Doctrine cache instance. Keep CacheUtility disabled by default; callers
        // should rely on local application caching or PSR implementations.
        if ($entityManager !== null) {
            // maintain backward compatibility if some code still sets a metadata cache object
            $meta = $entityManager->getMetadataCache();
            if ($meta !== null) {
                $this->cache = $meta;
                $this->enabled = true;
                $this->driver = $this->detectDriver();
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
        // Attempt to fetch stats if underlying cache exposes getStats()
        $stats = method_exists($this->cache, 'getStats') ? $this->cache->getStats() : null;
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

        // PSR-16 (simple cache)
        if (is_a($this->cache, Psr16CacheInterface::class, true) || (method_exists($this->cache, 'get') && method_exists($this->cache, 'set'))) {
            return $this->cache->get($key);
        }

        // PSR-6 (cache item pool)
        if (is_a($this->cache, Psr6CacheInterface::class, true) || method_exists($this->cache, 'getItem')) {
            $item = $this->cache->getItem($key);
            return $item->isHit() ? $item->get() : null;
        }

        // Legacy Doctrine cache API
        if (method_exists($this->cache, 'fetch')) {
            return $this->cache->fetch($key);
        }

        // Generic getter
        return method_exists($this->cache, 'get') ? $this->cache->get($key) : null;
    }

    public function has(string $key): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        if (is_a($this->cache, Psr16CacheInterface::class, true) || method_exists($this->cache, 'has')) {
            return $this->cache->has($key);
        }

        if (is_a($this->cache, Psr6CacheInterface::class, true) || method_exists($this->cache, 'getItem')) {
            $item = $this->cache->getItem($key);
            return $item->isHit();
        }

        return method_exists($this->cache, 'contains') ? $this->cache->contains($key) : false;
    }

    public function delete(string $key): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        if (is_a($this->cache, Psr16CacheInterface::class, true) || method_exists($this->cache, 'delete')) {
            return $this->cache->delete($key);
        }

        if (is_a($this->cache, Psr6CacheInterface::class, true) || method_exists($this->cache, 'deleteItem')) {
            return $this->cache->deleteItem($key);
        }

        return method_exists($this->cache, 'delete') ? $this->cache->delete($key) : false;
    }

    public function clear(): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        // PSR-16
        if (is_a($this->cache, Psr16CacheInterface::class, true) || method_exists($this->cache, 'clear')) {
            return $this->cache->clear();
        }

        // PSR-6
        if (is_a($this->cache, Psr6CacheInterface::class, true) || method_exists($this->cache, 'clear')) {
            return $this->cache->clear();
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

        // Only legacy caches expose server stats with key lists (eg. Memcached).
        // For PSR caches there's no standard way to list keys; return empty.
        $stats = method_exists($this->cache, 'getStats') ? $this->cache->getStats() : [];
        if (empty($stats)) {
            return [];
        }

        $keys = [];
        foreach ($stats as $serverKey => $serverStats) {
            if (isset($serverStats['keys']) && is_array($serverStats['keys'])) {
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

        if ($this->cache instanceof Psr16CacheInterface) {
            return 'psr16';
        }

        if ($this->cache instanceof Psr6CacheInterface) {
            return 'psr6';
        }

        $class = get_class($this->cache);
        if (str_contains($class, 'Array') || str_contains($class, 'ArrayCache')) {
            return 'array';
        }

        if (str_contains($class, 'Memcached')) {
            return 'memcached';
        }

        if (str_contains($class, 'Redis')) {
            return 'redis';
        }

        return 'unknown';
    }

    private function getDriverInfo(): array
    {
        return match ($this->driver) {
            'psr16' => [
                'type' => 'PSR-16',
                'description' => 'PSR-16 simple cache adapter',
                'extension' => 'psr/simple-cache',
            ],
            'psr6' => [
                'type' => 'PSR-6',
                'description' => 'PSR-6 cache item pool',
                'extension' => 'psr/cache',
            ],
            'memcached' => [
                'type' => 'Memcached',
                'description' => 'Legacy Memcached adapter',
                'extension' => 'memcached',
            ],
            'redis' => [
                'type' => 'Redis',
                'description' => 'Legacy Redis adapter',
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
