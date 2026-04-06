<?php

declare(strict_types=1);

namespace App\Handler;

use App\Event\FileCacheInvalidated;
use App\Event\FileCacheStored;
use Psr\Log\LoggerInterface;

class FileCacheEventHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onFileCacheStored(FileCacheStored $event): void
    {
        $fileCache = $event->getFileCache();
        $this->logger->info('File cache stored', [
            'key' => $fileCache->getKey(),
            'path' => $fileCache->getPath(),
            'size' => $fileCache->getSize(),
        ]);
    }

    public function onFileCacheInvalidated(FileCacheInvalidated $event): void
    {
        $fileCache = $event->getFileCache();
        $this->logger->info('File cache invalidated', [
            'key' => $fileCache->getKey(),
            'path' => $fileCache->getPath(),
        ]);
    }
}
