<?php

declare(strict_types=1);

namespace App\Command\Console;

use App\Console\Command\DatabaseCreateCommand;
use App\Command\Console\MigrationsMigrateCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DbSetupCommand extends Command
{
    protected static $defaultName = 'oryx:db:setup';
    protected static $defaultDescription = 'Setup database: create if needed and apply migrations';

    public function __construct()
    {
        parent::__construct(self::$defaultName, self::$defaultDescription);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Drop existing database if it exists (for SQLite)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show SQL that would be executed without running it'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Database Setup');

        // Step 1: Create database
        $io->section('Step 1: Creating database');
        $dbCreateCommand = new DatabaseCreateCommand();
        $dbCreateResult = $dbCreateCommand->run($input, $output);

        if ($dbCreateResult !== \Symfony\Component\Console\Command\Command::SUCCESS) {
            $io->error('Database creation failed');
            return $dbCreateResult;
        }

        // Step 2: Apply migrations
        $io->section('Step 2: Applying migrations');
        $migrationsCommand = new MigrationsMigrateCommand();
        $migrationsResult = $migrationsCommand->run($input, $output);

        if ($migrationsResult !== \Symfony\Component\Console\Command\Command::SUCCESS) {
            $io->error('Migration application failed');
            return $migrationsResult;
        }

        $io->success('Database setup completed successfully');
        return Command::SUCCESS;
    }
}
