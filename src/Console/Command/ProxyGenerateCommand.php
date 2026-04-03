<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\EnvironmentConfig;

class ProxyGenerateCommand extends Command
{
    protected static $defaultName = 'orm:proxy:generate';
    protected static $defaultDescription = 'Generate Doctrine proxy classes for production';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = getcwd();
        $envConfig = new EnvironmentConfig($projectRoot);

        $params = $envConfig->getDatabaseParams();
        $connection = DriverManager::getConnection($params);

        $proxyDir = $envConfig->getOrmProxyDir();
        $proxyNamespace = $envConfig->getOrmProxyNamespace();

        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, 0o755, true);
            $io->text(sprintf('Created proxy directory: <info>%s</info>', $proxyDir));
        }

        $config = new Configuration();
        $xmlDriver = new SimplifiedXmlDriver([
            $projectRoot . '/schema' => 'App\Entity',
        ], '.orm.xml');
        $config->setMetadataDriverImpl($xmlDriver);
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_NEVER);
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace($proxyNamespace);

        $em = DoctrineEntityManager::create($connection, $config);

        $proxyFactory = $em->getProxyFactory();
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        $proxyFactory->generateProxyClasses($metadatas);

        $io->success(sprintf('Proxy classes generated in %s', $proxyDir));

        return Command::SUCCESS;
    }
}
