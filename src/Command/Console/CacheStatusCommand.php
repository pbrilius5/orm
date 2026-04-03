<?php

declare(strict_types=1);

namespace App\Command\Console;

use Oryx\Cache\CacheUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class CacheStatusCommand extends Command
{
    protected static $defaultName = 'oryx:cache:status';
    protected static $defaultDescription = 'Show Doctrine cache status and statistics';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = \Oryx\ORM\EntityManagerFactory::getInstance();
        $cacheUtility = new CacheUtility($em);

        $stats = $cacheUtility->getStats();

        $output->writeln('<info>Doctrine Regional Cache Status</info>');
        $output->writeln('');

        $table = new Table($output);
        $table->setRows([
            ['Enabled', $stats['enabled'] ? '<info>Yes</info>' : '<comment>No</comment>'],
            ['Driver', $stats['driver']],
            ['App Environment', $stats['app_env'] ?? 'unknown'],
        ]);
        $table->render();

        if ($stats['enabled'] && isset($stats['driver_info'])) {
            $output->writeln('');
            $output->writeln('<info>Driver Information</info>');

            $driverTable = new Table($output);
            $driverTable->setRows([
                ['Type', $stats['driver_info']['type']],
                ['Description', $stats['driver_info']['description']],
                ['Extension', $stats['driver_info']['extension']],
            ]);
            $driverTable->render();
        }

        if ($stats['enabled'] && isset($stats['stats']) && !empty($stats['stats'])) {
            $output->writeln('');
            $output->writeln('<info>Cache Statistics</info>');

            foreach ($stats['stats'] as $server => $serverStats) {
                $output->writeln("<comment>Server: {$server}</comment>");

                $statsTable = new Table($output);
                $statsTable->setRows([
                    ['Items', $serverStats['items'] ?? 'N/A'],
                    ['Hits', $serverStats['hits'] ?? 'N/A'],
                    ['Misses', $serverStats['misses'] ?? 'N/A'],
                    ['Memory Usage', $this->formatBytes($serverStats['memory_usage'] ?? 0)],
                    ['Memory Free', $this->formatBytes($serverStats['memory_free'] ?? 0)],
                ]);
                $statsTable->render();
                $output->writeln('');
            }
        }

        return Command::SUCCESS;
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
