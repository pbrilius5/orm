<?php

declare(strict_types=1);

namespace App\Command\Console;

use App\Db;
use App\EnvironmentConfig;
use Oryx\ORM\EntityManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class DbCheckCommand extends Command
{
    protected static $defaultName = 'oryx:db:check';
    protected static $defaultDescription = 'Check database connection and status';

    public function __construct()
    {
        parent::__construct('oryx:db:check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>=== Database Check ===</info>');
        $output->writeln('');

        $this->showOrmStatus($output);
        $this->showCustomDbStatus($output);

        return Command::SUCCESS;
    }

    private function showOrmStatus(OutputInterface $output): void
    {
        $output->writeln('<comment>Doctrine ORM/DBAL (MySQL/SQLite)</comment>');

        try {
            $em = EntityManagerFactory::getInstance();
            $conn = $em->getConnection();
            $params = $conn->getParams();
            $driver = $params['driver'] ?? 'unknown';

            $table = new Table($output);
            $table->setRows([
                ['Driver', $driver],
                ['Connected', $conn->isConnected() ? '<info>Yes</info>' : '<comment>No</comment>'],
                ['Database', $params['dbname'] ?? $params['path'] ?? 'N/A'],
            ]);
            $table->render();

            if ($conn->isConnected()) {
                $output->writeln('');
                $output->writeln('<info>ORM Status: Connected via ' . strtoupper($driver) . '</info>');
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>ORM connection failed: ' . $e->getMessage() . '</error>');
        }

        $output->writeln('');
    }

    private function showCustomDbStatus(OutputInterface $output): void
    {
        $output->writeln('<comment>Custom PDO Singleton (SQLite only)</comment>');

        try {
            $envConfig = new EnvironmentConfig();
            $params = $envConfig->getDatabaseParams();

            $table = new Table($output);
            $table->setRows([
                ['Driver', $params['driver']],
                ['Path', $params['path'] ?? ':memory:'],
            ]);
            $table->render();

            if ($params['driver'] === 'pdo_sqlite') {
                $db = Db::getInstance();
                $stmt = $db->query('SELECT 1 as test');
                $result = $stmt->fetch();

                $output->writeln('');
                $output->writeln('<info>Custom PDO SQLite: ' . ($result['test'] === 1 ? 'OK' : 'FAILED') . '</info>');
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>Custom PDO failed: ' . $e->getMessage() . '</error>');
        }

        $output->writeln('');
    }
}
