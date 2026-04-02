<?php

declare(strict_types=1);

namespace Oryx\ORM;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use App\EnvironmentConfig;

/**
 * Factory for creating Oryx ORM EntityManager instances.
 */
class EntityManagerFactory
{
    /**
     * Create EntityManager from environment variables.
     */
    public static function createFromEnv(): EntityManager
    {
        $envConfig = new EnvironmentConfig();

        return self::create(
            $envConfig->getDatabaseParams(),
            dirname(__DIR__, 3) . '/schema',
            $envConfig->getOrmProxyAutoGenerate(),
            $envConfig->getOrmProxyDir(),
            $envConfig->getOrmProxyNamespace(),
        );
    }

    /**
     * Create EntityManager with custom configuration.
     */
    public static function create(array $connectionParams, string $schemaPath, int $autoGenerateProxy = \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL, string $proxyDir = null, string $proxyNamespace = null): EntityManager
    {
        $connection = DriverManager::getConnection($connectionParams);

        $config = new Configuration();

        $driver = new SimplifiedXmlDriver([
            $schemaPath => 'App\Entity',
        ], '.orm.xml');
        $config->setMetadataDriverImpl($driver);

        $config->setAutoGenerateProxyClasses($autoGenerateProxy);
        $config->setProxyDir($proxyDir ?? sys_get_temp_dir());
        $config->setProxyNamespace($proxyNamespace ?? 'Oryx\ORM\Proxy');

        return EntityManager::create($connection, $config);
    }

    /**
     * Create EntityManager with SQLite in-memory (for testing).
     */
    public static function createForTesting(string $schemaPath): EntityManager
    {
        $envConfig = new EnvironmentConfig();
        return self::create($envConfig->getDatabaseParamsForTesting(), $schemaPath);
    }
}
