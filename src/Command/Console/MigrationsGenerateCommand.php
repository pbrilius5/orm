<?php

declare(strict_types=1);

namespace App\Command\Console;

use App\EnvironmentConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Oryx\ORM\Mapping\Driver\XmlThenAttributeDriver;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TypeError;

class MigrationsGenerateCommand extends Command
{
    protected static $defaultName = 'oryx:migrations:generate';
    protected static $defaultDescription = 'Generate SQL migrations from entity metadata using SchemaTool';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Output file for generated SQL (defaults to stdout)',
                null
            )
            ->addOption(
                'entity-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to entity directory',
                null
            )
            ->addOption(
                'schema-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to schema directory',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = getcwd();

        try {
            $entityPath = $input->getOption('entity-path') ?? $projectRoot . '/src/Entity';
            $schemaPath = $input->getOption('schema-path') ?? $projectRoot . '/schema';
            $outputFile = $input->getOption('output');

            $io->title('Generating SQL migrations from entity metadata');

            // Load environment config
            $envConfig = new EnvironmentConfig($projectRoot);
            $connectionParams = $envConfig->getDatabaseParams();

            // Add UUID type if not exists
            if (!\Doctrine\DBAL\Types\Type::hasType('uuid')) {
                \Doctrine\DBAL\Types\Type::addType('uuid', UuidType::class);
            }

            // Create connection
            $connection = DriverManager::getConnection($connectionParams);

            // Create Doctrine ORM configuration (same as EntityManager)
            $doctrineConfig = new Configuration();
            $driver = new XmlThenAttributeDriver($schemaPath, $entityPath);
            $doctrineConfig->setMetadataDriverImpl($driver);
            $doctrineConfig->setAutoGenerateProxyClasses(
                \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER
            );
            $doctrineConfig->setProxyDir(sys_get_temp_dir());
            $doctrineConfig->setProxyNamespace('Oryx\ORM\Proxy');

            // Create EntityManager
            $entityManager = new EntityManager($connection, $doctrineConfig);

            // Get all metadata
            $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

            if (empty($metadatas)) {
                $io->warning('No entity metadata found to generate migrations for');
                return Command::SUCCESS;
            }

            // Create SchemaTool
            $schemaTool = new SchemaTool($entityManager);

            // Get SQL for creating schema
            $queries = $schemaTool->getCreateSchemaSql($metadatas);

            if (empty($queries)) {
                $io->info('No schema changes detected - database is up to date');
                return Command::SUCCESS;
            }

            $io->text('Generated SQL for the following entities:');
            foreach ($metadatas as $metadata) {
                $io->text('  • ' . $metadata->getName());
            }

            $io->newLine();

            // Output SQL
            $fullSql = implode(";\n\n", $queries) . ";";

            if ($outputFile) {
                file_put_contents($outputFile, $fullSql);
                $io->success("SQL migration written to: {$outputFile}");
            } else {
                $io->text('Generated SQL:');
                $io->text($fullSql);
            }

            return Command::SUCCESS;
        } catch (ToolsException $e) {
            $io->error('SchemaTool error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error('Unexpected error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
