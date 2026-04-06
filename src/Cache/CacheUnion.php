<?php

declare(strict_types=1);

namespace App\Cache;

use App\Db;
use App\Service\PersistentSingletonRegistry;
use League\Flysystem\FilesystemOperator;
use RuntimeException;

class CacheUnion
{
    private static ?CacheUnion $instance = null;
    private static ?FilesystemOperator $filesystem = null;

    private array $memory = [];
    private ?PersistentSingletonRegistry $dbCache = null;
    private bool $dbEnabled = false;
    private bool $flysystemEnabled = false;

    public static function getInstance(
        ?FilesystemOperator $filesystem = null,
        ?PersistentSingletonRegistry $dbCache = null
    ): CacheUnion {
        self::$filesystem = $filesystem;

        if (self::$instance === null) {
            self::$instance = new self($dbCache);
        }

        return self::$instance;
    }

    public function __construct(?PersistentSingletonRegistry $dbCache = null)
    {
        $this->dbCache = $dbCache;
        $this->dbEnabled = $dbCache !== null;
        $this->flysystemEnabled = self::$filesystem !== null;
    }

    public function get(string $key, int $ttl = 3600): mixed
    {
        if (isset($this->memory[$key])) {
            $item = $this->memory[$key];
            if ($item['expires'] === 0 || $item['expires'] > time()) {
                return $item['value'];
            }
            unset($this->memory[$key]);
        }

        if ($this->flysystemEnabled && self::$filesystem !== null) {
            $value = $this->getFromFlysystem($key);
            if ($value !== null) {
                $this->setMemory($key, $value, $ttl);
                return $value;
            }
        }

        if ($this->dbEnabled && $this->dbCache !== null) {
            $value = $this->dbCache->get($key);
            if ($value !== null) {
                $this->setMemory($key, $value, $ttl);
                return $value;
            }
        }

        return null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->setMemory($key, $value, $ttl);

        if ($this->flysystemEnabled && self::$filesystem !== null) {
            $this->setToFlysystem($key, $value);
        }

        if ($this->dbEnabled && $this->dbCache !== null) {
            $this->dbCache->set($key, $value);
        }

        return true;
    }

    public function has(string $key): bool
    {
        if (isset($this->memory[$key])) {
            $item = $this->memory[$key];
            if ($item['expires'] === 0 || $item['expires'] > time()) {
                return true;
            }
            unset($this->memory[$key]);
        }

        if ($this->flysystemEnabled && self::$filesystem !== null) {
            if ($this->hasFlysystem($key)) {
                return true;
            }
        }

        if ($this->dbEnabled && $this->dbCache !== null) {
            if ($this->dbCache->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function delete(string $key): bool
    {
        unset($this->memory[$key]);

        if ($this->flysystemEnabled && self::$filesystem !== null) {
            $this->deleteFromFlysystem($key);
        }

        if ($this->dbEnabled && $this->dbCache !== null) {
            $this->dbCache->remove($key);
        }

        return true;
    }

    public function clear(): bool
    {
        $this->memory = [];

        if ($this->flysystemEnabled && self::$filesystem !== null) {
            $this->clearFlysystem();
        }

        if ($this->dbEnabled && $this->dbCache !== null) {
            $this->dbCache->clearCache();
        }

        return true;
    }

    public function isEnabled(): array
    {
        return [
            'memory' => true,
            'flysystem' => $this->flysystemEnabled,
            'db' => $this->dbEnabled,
        ];
    }

    private function setMemory(string $key, mixed $value, int $ttl): void
    {
        $expires = $ttl > 0 ? time() + $ttl : 0;
        $this->memory[$key] = [
            'value' => $value,
            'expires' => $expires,
        ];
    }

    private function getFromFlysystem(string $key): ?string
    {
        if (self::$filesystem === null) {
            return null;
        }

        $path = $this->resolveCachePath($key);

        try {
            return self::$filesystem->read($path);
        } catch (\League\Flysystem\UnableToReadFile $e) {
            return null;
        }
    }

    private function setToFlysystem(string $key, mixed $value): void
    {
        if (self::$filesystem === null) {
            return;
        }

        $path = $this->resolveCachePath($key);
        $content = is_string($value) ? $value : serialize($value);

        try {
            self::$filesystem->write($path, $content);
        } catch (\League\Flysystem\UnableToWriteFile $e) {
            throw new RuntimeException('Failed to write to cache: ' . $e->getMessage(), 0, $e);
        }
    }

    private function hasFlysystem(string $key): bool
    {
        if (self::$filesystem === null) {
            return false;
        }

        $path = $this->resolveCachePath($key);

        return self::$filesystem->fileExists($path);
    }

    private function deleteFromFlysystem(string $key): void
    {
        if (self::$filesystem === null) {
            return;
        }

        $path = $this->resolveCachePath($key);

        try {
            self::$filesystem->delete($path);
        } catch (\League\Flysystem\UnableToDeleteFile $e) {
        }
    }

    private function clearFlysystem(): void
    {
        if (self::$filesystem === null) {
            return;
        }

        try {
            self::$filesystem->deleteDirectory('cache/');
            self::$filesystem->createDirectory('cache/');
        } catch (\League\Flysystem\UnableToCreateDirectory $e) {
        }
    }

    private function resolveCachePath(string $key): string
    {
        return 'cache/' . md5($key) . '.cache';
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
