<?php

declare(strict_types=1);

namespace App\Container;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use App\Service\FlysystemService;

class FlysystemServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        FilesystemOperator::class,
        FlysystemService::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }

    public function register(): void
    {
        $this->getContainer()->addShared(FilesystemOperator::class, function () {
            $storagePath = getenv('FLYSYSTEM_STORAGE_PATH') ?: dirname(__DIR__, 2) . '/var/storage';
            $adapter = new LocalFilesystemAdapter($storagePath);
            return new Filesystem($adapter);
        });

        $this->getContainer()->addShared(FlysystemService::class);
    }
}
