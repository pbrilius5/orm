<?php

declare(strict_types=1);

namespace App;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PDO;
use PDOStatement;

class Db
{
    private static ?PDO $instance = null;
    private static string $path = 'var/data/orm.db';
    private static ?FilesystemOperator $fs = null;
    private static bool $fsInitialized = false;

    public static function getInstance(?string $path = null): PDO
    {
        if ($path !== null) {
            self::$path = $path;
        }

        if (self::$instance === null) {
            self::$instance = self::create();
        }

        return self::$instance;
    }

    public static function getFilesystem(): FilesystemOperator
    {
        if (self::$fs === null) {
            self::initFilesystem();
        }

        return self::$fs;
    }

    public static function setFilesystem(FilesystemOperator $fs): void
    {
        self::$fs = $fs;
        self::$fsInitialized = true;
    }

    private static function initFilesystem(): void
    {
        if (self::$fsInitialized) {
            return;
        }

        $storagePath = getenv('FLYSYSTEM_STORAGE_PATH') ?: dirname(__DIR__, 2) . '/var/storage';
        $adapter = new LocalFilesystemAdapter($storagePath);
        self::$fs = new \League\Flysystem\Filesystem($adapter);
        self::$fsInitialized = true;
    }

    private static function create(): PDO
    {
        $dir = dirname(self::$path);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $dsn = 'sqlite:' . self::$path;

        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        self::initializeSchema($pdo);

        return $pdo;
    }

    private static function initializeSchema(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA cache_size = -2000');
        $pdo->exec('PRAGMA temp_store = MEMORY');
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function setPath(string $path): void
    {
        self::$path = $path;
    }

    public static function getPath(): string
    {
        return self::$path;
    }

    public function prepare(string $sql): PDOStatement|false
    {
        return self::$instance?->prepare($sql);
    }

    public function query(string $sql): PDOStatement|false
    {
        return self::$instance?->query($sql);
    }

    public function exec(string $sql): int|false
    {
        return self::$instance?->exec($sql);
    }

    public function lastInsertId(): string|false
    {
        return self::$instance?->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return self::$instance?->beginTransaction() ?? false;
    }

    public function commit(): bool
    {
        return self::$instance?->commit() ?? false;
    }

    public function rollBack(): bool
    {
        return self::$instance?->rollBack() ?? false;
    }

    public function inTransaction(): bool
    {
        return self::$instance?->inTransaction() ?? false;
    }

    public static function writeCache(string $key, string $content): void
    {
        $fs = self::getFilesystem();
        $path = 'cache/' . md5($key) . '.cache';

        $fs->write($path, $content);
    }

    public static function readCache(string $key): ?string
    {
        $fs = self::getFilesystem();
        $path = 'cache/' . md5($key) . '.cache';

        try {
            return $fs->read($path);
        } catch (\League\Flysystem\UnableToReadFile $e) {
            return null;
        }
    }

    public static function hasCache(string $key): bool
    {
        $fs = self::getFilesystem();
        $path = 'cache/' . md5($key) . '.cache';

        return $fs->fileExists($path);
    }

    public static function deleteCache(string $key): void
    {
        $fs = self::getFilesystem();
        $path = 'cache/' . md5($key) . '.cache';

        try {
            $fs->delete($path);
        } catch (\League\Flysystem\UnableToDeleteFile $e) {
        }
    }

    public static function writeStorage(string $path, string $content): void
    {
        $fs = self::getFilesystem();
        $fs->write($path, $content);
    }

    public static function readStorage(string $path): ?string
    {
        $fs = self::getFilesystem();

        try {
            return $fs->read($path);
        } catch (\League\Flysystem\UnableToReadFile $e) {
            return null;
        }
    }

    public static function hasStorage(string $path): bool
    {
        $fs = self::getFilesystem();

        return $fs->fileExists($path);
    }

    public static function deleteStorage(string $path): void
    {
        $fs = self::getFilesystem();

        try {
            $fs->delete($path);
        } catch (\League\Flysystem\UnableToDeleteFile $e) {
        }
    }
}
