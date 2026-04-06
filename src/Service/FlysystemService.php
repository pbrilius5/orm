<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FileCache;
use App\Event\FileCacheInvalidated;
use App\Event\FileCacheStored;
use App\Event\DomainEventEmitter;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use Psr\Log\LoggerInterface;
use RuntimeException;

class FlysystemService
{
    private FilesystemOperator $filesystem;
    private EntityManagerInterface $entityManager;
    private DomainEventEmitter $eventEmitter;
    private LoggerInterface $logger;

    public function __construct(FilesystemOperator $filesystem, EntityManagerInterface $entityManager, DomainEventEmitter $eventEmitter, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->entityManager = $entityManager;
        $this->eventEmitter = $eventEmitter;
        $this->logger = $logger;
    }

    public function store(string $key, string $content, ?\DateTimeInterface $expiresAt = null): FileCache
    {
        $path = $this->resolvePath($key);

        try {
            $this->filesystem->write($path, $content);
        } catch (UnableToWriteFile $e) {
            $this->logger->error('Failed to write file to cache', [
                'key' => $key,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to write file to cache: ' . $e->getMessage(), 0, $e);
        }

        $fileCache = $this->entityManager->getRepository(FileCache::class)->findOneBy(['key' => $key]);

        if ($fileCache === null) {
            $fileCache = new FileCache();
            $fileCache->setKey($key);
        }

        $fileCache->setPath($path);
        $fileCache->setHash(hash('sha256', $content));
        $fileCache->setSize(strlen($content));
        $fileCache->setExpiresAt($expiresAt);
        $fileCache->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($fileCache);
        $this->entityManager->flush();

        $this->eventEmitter->emit(new FileCacheStored($fileCache));

        return $fileCache;
    }

    public function retrieve(string $key): ?string
    {
        $fileCache = $this->entityManager->getRepository(FileCache::class)->findOneBy(['key' => $key]);

        if ($fileCache === null) {
            return null;
        }

        if ($fileCache->isExpired()) {
            $this->invalidate($key);
            return null;
        }

        try {
            return $this->filesystem->read($fileCache->getPath());
        } catch (UnableToReadFile $e) {
            $this->invalidate($key);
            return null;
        }
    }

    public function invalidate(string $key): void
    {
        $fileCache = $this->entityManager->getRepository(FileCache::class)->findOneBy(['key' => $key]);

        if ($fileCache !== null) {
            try {
                $this->filesystem->delete($fileCache->getPath());
            } catch (UnableToDeleteFile $e) {
                $this->logger->warning('Failed to delete file during cache invalidation', [
                    'key' => $key,
                    'path' => $fileCache->getPath(),
                    'error' => $e->getMessage(),
                ]);
            }

            $this->entityManager->remove($fileCache);
            $this->entityManager->flush();
        }
    }

    public function exists(string $key): bool
    {
        $fileCache = $this->entityManager->getRepository(FileCache::class)->findOneBy(['key' => $key]);

        if ($fileCache === null) {
            return false;
        }

        if ($fileCache->isExpired()) {
            $this->invalidate($key);
            return false;
        }

        return $this->filesystem->fileExists($fileCache->getPath());
    }

    public function getMetadata(string $key): ?FileCache
    {
        $fileCache = $this->entityManager->getRepository(FileCache::class)->findOneBy(['key' => $key]);

        if ($fileCache === null) {
            return null;
        }

        if ($fileCache->isExpired()) {
            $this->invalidate($key);
            return null;
        }

        return $fileCache;
    }

    private function resolvePath(string $key): string
    {
        return 'cache/' . str_replace(['/', '\\'], '_', $key);
    }
}
