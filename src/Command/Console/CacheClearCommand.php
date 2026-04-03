<?php

declare(strict_types=1);

namespace App\Command\Console;

use Oryx\Cache\CacheUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends Command
{
    protected static $defaultName = 'oryx:cache:clear';
    protected static $defaultDescription = 'Clear Doctrine cache (metadata and query cache)';

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = \Oryx\ORM\EntityManagerFactory::getInstance();
        $cacheUtility = new CacheUtility($em);

        if (!$cacheUtility->isEnabled()) {
            $output->writeln('<comment>Cache is not enabled - nothing to clear</comment>');
            return Command::SUCCESS;
        }

        $force = $input->getOption('force');

        if (!$force) {
            $output->writeln('<comment>Cache clear requires --force flag for safety.</comment>');
            $output->writeln('<comment>Example: php bin/console oryx:cache:clear --force</comment>');
            return Command::SUCCESS;
        }

        $result = $cacheUtility->clear();

        if ($result) {
            $output->writeln('<info>Cache cleared successfully</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<error>Failed to clear cache</error>');
        return Command::FAILURE;
    }
}
