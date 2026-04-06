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

class MigrationsMigrateCommand extends Command
{
    protected static $defaultName = 'oryx:migrations:migrate';
    protected static $defaultDescription = 'Execute SQL migrations against the database using SchemaTool';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show SQL that would be executed without running it'
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
            $dryRun = $input->getOption('dry-run');

            $io->title('Applying schema migrations using SchemaTool');

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
                $io->warning('No entity metadata found to migrate');
                return Command::SUCCESS;
            }

            // Create SchemaTool
            $schemaTool = new SchemaTool($entityManager);

            // Get SQL for creating/updating schema
            $queries = $schemaTool->getUpdateSchemaSql($metadatas);

            if (empty($queries)) {
                $io->info('Database schema is already up to date');
                return Command::SUCCESS;
            }

            $io->text('SQL to be executed:');
            foreach ($queries as $i => $query) {
                $io->text(sprintf('  [%d] %s', $i + 1, $query));
            }

            if ($dryRun) {
                $io->warning('Dry run mode - no changes were made to the database');
                return Command::SUCCESS;
            }

            // Execute the schema changes
            $schemaTool->updateSchema($metadatas);

            $io->success('Schema migration completed successfully');
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
