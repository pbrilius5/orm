<?php

declare(strict_types=1);

namespace App\Command\Console;

use Oryx\Cache\CacheUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheQueryCommand extends Command
{
    protected static $defaultName = 'oryx:cache:query';
    protected static $defaultDescription = 'Query Doctrine cache keys and values';

    public function __construct()
    {
        parent::__construct('oryx:cache:query');
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::OPTIONAL, 'Cache key to retrieve');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Pattern to match keys (default: all keys)');
        $this->addOption('has', null, InputOption::VALUE_NONE, 'Check if key exists');
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'Delete specified key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = \Oryx\ORM\EntityManagerFactory::getInstance();
        $cacheUtility = new CacheUtility($em);

        if (!$cacheUtility->isEnabled()) {
            $output->writeln('<error>Cache is not enabled</error>');
            return Command::FAILURE;
        }

        $key = $input->getArgument('key');
        $pattern = $input->getOption('pattern');
        $hasFlag = $input->getOption('has');
        $deleteFlag = $input->getOption('delete');

        if ($hasFlag && $key) {
            $exists = $cacheUtility->has($key);
            $output->writeln($exists
                ? "<info>Key exists: {$key}</info>"
                : "<comment>Key not found: {$key}</comment>");
            return Command::SUCCESS;
        }

        if ($deleteFlag && $key) {
            $result = $cacheUtility->delete($key);
            $output->writeln($result
                ? "<info>Key deleted: {$key}</info>"
                : "<error>Failed to delete key: {$key}</error>");
            return $result ? Command::SUCCESS : Command::FAILURE;
        }

        if ($key) {
            $value = $cacheUtility->get($key);
            if ($value === false || $value === null) {
                $output->writeln("<comment>No value found for key: {$key}</comment>");
                return Command::SUCCESS;
            }

            $output->writeln("<info>Value for key: {$key}</info>");
            $output->writeln('');

            if (is_array($value) || is_object($value)) {
                $output->writeln(json_encode($value, JSON_PRETTY_PRINT));
            } else {
                $output->writeln((string) $value);
            }

            return Command::SUCCESS;
        }

        $patternToUse = $pattern ?? '*';
        $keys = $cacheUtility->getKeys($patternToUse);

        if (empty($keys)) {
            $output->writeln("<comment>No keys match pattern: {$patternToUse}</comment>");
            return Command::SUCCESS;
        }

        $output->writeln('<info>Found ' . count($keys) . ' key(s) matching pattern: ' . $patternToUse . '</info>');
        $output->writeln('');

        foreach (array_slice($keys, 0, 50) as $k) {
            $output->writeln('  - ' . $k);
        }

        if (count($keys) > 50) {
            $output->writeln('  ... and ' . (count($keys) - 50) . ' more');
        }

        return Command::SUCCESS;
    }
}
