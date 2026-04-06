<?php

declare(strict_types=1);

namespace App\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\EnvironmentConfig;

class ProxyClearCommand extends Command
{
    protected static $defaultName = 'orm:proxy:clear';
    protected static $defaultDescription = 'Clear all Doctrine proxy classes';

    public function __construct()
    {
        parent::__construct('orm:proxy:clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);

        $proxyDir = $envConfig->getOrmProxyDir();

        if (!is_dir($proxyDir)) {
            $io->note(sprintf('Proxy directory does not exist: %s', $proxyDir));
            return Command::SUCCESS;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($proxyDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
                $count++;
            }
        }

        $io->success(sprintf('Cleared %d proxy file(s) from %s', $count, $proxyDir));

        return Command::SUCCESS;
    }
}
