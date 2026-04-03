<?php

declare(strict_types=1);

namespace App\Console\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\EnvironmentConfig;

class DatabaseCreateCommand extends Command
{
    protected static $defaultName = 'oryx:db:create';
    protected static $defaultDescription = 'Create SQLite database and schema';

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Drop existing database if it exists'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);

        $params = $envConfig->getDatabaseParams();
        $driver = $params['driver'];

        if ($driver === 'pdo_mysql') {
            return $this->createMySQL($io, $params);
        }

        return $this->createSQLite($io, $projectRoot, $params, $input->getOption('force'));
    }

    private function createSQLite(SymfonyStyle $io, string $projectRoot, array $params, bool $force): int
    {
        $dbPath = $params['path'] ?? ($projectRoot . '/var/data/orm.db');
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0o755, true);
            $io->text(sprintf('Created directory: <info>%s</info>', $dbDir));
        }

        if (file_exists($dbPath)) {
            if ($force) {
                unlink($dbPath);
                $io->text('Dropped existing database');
            } else {
                $io->warning('Database already exists. Use --force to recreate');
                return Command::SUCCESS;
            }
        }

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $dbPath,
        ]);

        $io->text(sprintf('Created SQLite database: <info>%s</info>', $dbPath));

        $config = new \Doctrine\ORM\Configuration();
        $xmlDriver = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver([
            $projectRoot . '/schema' => 'App\Entity',
        ], '.orm.xml');
        $config->setMetadataDriverImpl($xmlDriver);
        $config->setAutoGenerateProxyClasses(\Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Oryx\ORM\Proxy');

        $em = \Doctrine\ORM\EntityManager::create($connection, $config);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadatas);

        $io->success('Database schema created successfully');

        return Command::SUCCESS;
    }

    private function createMySQL(SymfonyStyle $io, array $params): int
    {
        $adminParams = [
            'driver' => $params['driver'],
            'host' => $params['host'],
            'port' => $params['port'],
            'user' => $params['user'],
            'password' => $params['password'],
        ];

        $adminConnection = DriverManager::getConnection($adminParams);

        $dbName = $params['dbname'];
        $charset = $params['charset'] ?? 'utf8mb4';

        try {
            $adminConnection->executeStatement(
                sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci', $dbName, $charset, $charset)
            );
            $io->success(sprintf('Database `%s` created', $dbName));
        } catch (\Throwable $e) {
            $io->error('Failed to create database: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
