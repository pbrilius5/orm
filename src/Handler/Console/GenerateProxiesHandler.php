<?php

declare(strict_types=1);

namespace App\Handler\Console;

use App\Command\Console\GenerateProxiesCommand;
use App\EnvironmentConfig;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Psr\Log\LoggerInterface;

class GenerateProxiesHandler
{
    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function handle(GenerateProxiesCommand $command): bool
    {
        $this->logger?->info('Generating proxies');

        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);
        $connectionParams = $envConfig->getDatabaseParams();

        $connection = DriverManager::getConnection($connectionParams);

        $doctrineConfig = new Configuration();
        $xmlDriver = new SimplifiedXmlDriver([
            $projectRoot . '/schema' => 'App\Entity',
        ], '.orm.xml');
        $doctrineConfig->setMetadataDriverImpl($xmlDriver);
        $doctrineConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_ALWAYS);
        $doctrineConfig->setProxyDir($envConfig->get('orm.proxy_dir') ?? sys_get_temp_dir() . '/orm/proxies');
        $doctrineConfig->setProxyNamespace('Oryx\ORM\Proxy');

        $em = DoctrineEntityManager::create($connection, $doctrineConfig);

        $this->logger?->info('Proxies generated successfully');

        return true;
    }
}
