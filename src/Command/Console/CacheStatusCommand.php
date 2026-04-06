<?php

declare(strict_types=1);

namespace App\Command\Console;

use App\Cache\CacheUnion;
use App\Db;
use Oryx\Cache\CacheUtility;
use Oryx\ORM\EntityManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class CacheStatusCommand extends Command
{
    protected static $defaultName = 'oryx:cache:status';
    protected static $defaultDescription = 'Show cache status and statistics';

    public function __construct()
    {
        parent::__construct('oryx:cache:status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== Cache Status ===</info>');
        $output->writeln('');

        $this->showDoctrineCache($output);
        $this->showCacheUnionStatus($output);
        $this->showDbStatus($output);
        $this->showFlysystemStatus($output);

        return Command::SUCCESS;
    }

    private function showDoctrineCache(OutputInterface $output): void
    {
        $output->writeln('<comment>Doctrine Regional Cache</comment>');

        try {
            $em = EntityManagerFactory::getInstance();
            $cacheUtility = new CacheUtility($em);
            $stats = $cacheUtility->getStats();

            $table = new Table($output);
            $table->setRows([
                ['Enabled', $stats['enabled'] ? '<info>Yes</info>' : '<comment>No</comment>'],
                ['Driver', $stats['driver']],
                ['App Environment', $stats['app_env'] ?? 'unknown'],
            ]);
            $table->render();
        } catch (\Throwable $e) {
            $output->writeln('<comment>Doctrine cache not available: ' . $e->getMessage() . '</comment>');
        }

        $output->writeln('');
    }

    private function showCacheUnionStatus(OutputInterface $output): void
    {
        $output->writeln('<comment>CacheUnion (3-Tier Compound)</comment>');

        try {
            $cache = CacheUnion::getInstance();
            $enabled = $cache->isEnabled();

            $table = new Table($output);
            $table->setRows([
                ['L1 (Memory)', $enabled['memory'] ? '<info>enabled</info>' : '<comment>disabled</comment>'],
                ['L2 (Flysystem)', $enabled['flysystem'] ? '<info>enabled</info>' : '<comment>disabled</comment>'],
                ['L3 (DB)', $enabled['db'] ? '<info>enabled</info>' : '<comment>disabled</comment>'],
            ]);
            $table->render();
        } catch (\Throwable $e) {
            $output->writeln('<comment>CacheUnion not initialized: ' . $e->getMessage() . '</comment>');
        }

        $output->writeln('');
    }

    private function showDbStatus(OutputInterface $output): void
    {
        $output->writeln('<comment>Db (Custom PDO Singleton)</comment>');

        try {
            $db = Db::getInstance();

            $table = new Table($output);
            $table->setRows([
                ['Driver', 'pdo_sqlite'],
                ['Path', Db::getPath()],
                ['In Transaction', $db->inTransaction() ? '<comment>yes</comment>' : 'no'],
            ]);
            $table->render();
        } catch (\Throwable $e) {
            $output->writeln('<comment>Db not available: ' . $e->getMessage() . '</comment>');
        }

        $output->writeln('');
    }

    private function showFlysystemStatus(OutputInterface $output): void
    {
        $output->writeln('<comment>Flysystem (Storage)</comment>');

        try {
            $fs = Db::getFilesystem();

            $table = new Table($output);
            $table->setRows([
                ['Storage Path', getenv('FLYSYSTEM_STORAGE_PATH') ?: dirname(__DIR__, 2) . '/var/storage'],
                ['Cache Path', 'storage/cache/'],
            ]);
            $table->render();
        } catch (\Throwable $e) {
            $output->writeln('<comment>Flysystem not initialized</comment>');
        }

        $output->writeln('');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
