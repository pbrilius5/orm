<?php

declare(strict_types=1);

namespace App\Console\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\EnvironmentConfig;

/**
 * Generates entity classes and method stubs from your mapping information.
 */
class GenerateEntitiesCommand extends Command
{
    protected static $defaultName = 'orm:generate:entities';
    protected static $defaultDescription = 'Generates entity classes and method stubs from mapping information';

    private string $outputDir;

    public function __construct(string $outputDir = null)
    {
        $this->outputDir = $outputDir ?? (getcwd() . '/src/Entity');

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'output-dir',
                InputArgument::OPTIONAL,
                'The directory where to write the generated classes.',
                $this->outputDir
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Generate only the classes matching the filter. '
                . 'A filter could be a namespace (ending with \*) or a class name.'
            )
            ->addOption(
                'no-backup',
                null,
                InputOption::VALUE_NONE,
                'If set, no backup of the existing file will be created.'
            )
            ->addOption(
                'update-if-empty',
                null,
                InputOption::VALUE_NONE,
                'If set, EntityGenerator will not overwrite existing files.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);

        $connectionParams = $envConfig->getDatabaseParams();

        try {
            $connection = DriverManager::getConnection($connectionParams);
        } catch (\Throwable $e) {
            $io->warning('Could not connect to the database: ' . $e->getMessage());
            $io->warning('Using an in-memory SQLite database for metadata generation.');

            $connection = DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ]);
        }

        $doctrineConfig = new Configuration();

        $xmlDriver = new SimplifiedXmlDriver([
            $projectRoot . '/schema' => 'App\Entity',
        ], '.orm.xml');
        $doctrineConfig->setMetadataDriverImpl($xmlDriver);

        $doctrineConfig->setAutoGenerateProxyClasses(
            ProxyFactory::AUTOGENERATE_NEVER
        );
        $doctrineConfig->setProxyDir(sys_get_temp_dir());
        $doctrineConfig->setProxyNamespace('Oryx\ORM\Proxy');

        $entityManager = DoctrineEntityManager::create($connection, $doctrineConfig);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        if (empty($metadatas)) {
            $io->warning('No metadata classes to process.');

            return Command::SUCCESS;
        }

        $filter = $input->getOption('filter');
        if ($filter) {
            $filteredMetadatas = [];
            foreach ($metadatas as $metadata) {
                foreach ($filter as $pattern) {
                    if (fnmatch($pattern, $metadata->getName())) {
                        $filteredMetadatas[] = $metadata;
                        continue 2;
                    }
                }
            }
            $metadatas = $filteredMetadatas;
        }

        if (empty($metadatas)) {
            $io->warning('No metadata classes match the filter.');

            return Command::SUCCESS;
        }

        $io->title('Generating entity classes');

        $generator = new EntityGenerator();
        $generator->setUpdateEntityIfExists($input->getOption('update-if-empty'));
        $generator->setGenerateStubMethods(true);
        $generator->setRegenerateEntityIfExists(false);
        $generator->setNumSpaces(4);

        $generatedCount = 0;
        foreach ($metadatas as $metadata) {
            $className = $metadata->getName();
            $io->text(sprintf('Processing entity "<info>%s</info>"', $className));

            try {
                $generator->generate([$metadata], $this->outputDir, !$input->getOption('no-backup'));
                $io->text(sprintf('  > Generated <info>%s</info>', $className));
                $generatedCount++;
            } catch (\Throwable $e) {
                $io->error(sprintf('  > Failed to generate entity "<info>%s</info>: %s', $className, $e->getMessage()));
            }
        }

        $io->success(sprintf('Generated %d entity classes', $generatedCount));

        return Command::SUCCESS;
    }
}
