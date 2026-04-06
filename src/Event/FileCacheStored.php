<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\FileCache;

class FileCacheStored extends DomainEvent
{
    private FileCache $fileCache;

    public function __construct(FileCache $fileCache)
    {
        $this->fileCache = $fileCache;
    }

    public function getFileCache(): FileCache
    {
        return $this->fileCache;
    }
}
