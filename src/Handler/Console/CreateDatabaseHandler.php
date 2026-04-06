<?php

declare(strict_types=1);

namespace App\Handler\Console;

use App\Command\Console\CreateDatabaseCommand;
use App\EnvironmentConfig;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Proxy\ProxyFactory;
use Oryx\ORM\Mapping\Driver\XmlThenAttributeDriver;
use Psr\Log\LoggerInterface;

class CreateDatabaseHandler
{
    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function handle(CreateDatabaseCommand $command): bool
    {
        $this->logger?->info('Creating database schema');

        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);
        $connectionParams = $envConfig->getDatabaseParams();

        $connection = DriverManager::getConnection($connectionParams);

        $doctrineConfig = new Configuration();
        $driver = new XmlThenAttributeDriver($projectRoot . '/schema', $projectRoot . '/src/Entity');
        $doctrineConfig->setMetadataDriverImpl($driver);
        $doctrineConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_NEVER);
        $doctrineConfig->setProxyDir(sys_get_temp_dir());
        $doctrineConfig->setProxyNamespace('Oryx\ORM\Proxy');

        $em = DoctrineEntityManager::create($connection, $doctrineConfig);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        if ($command->force) {
            $schemaTool->dropSchema($metadatas);
        }
        $schemaTool->createSchema($metadatas);

        $this->logger?->info('Database schema created successfully');

        return true;
    }
}
