<?php

declare(strict_types=1);

namespace Oryx\ORM;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use App\EnvironmentConfig;
use Ramsey\Uuid\Doctrine\UuidType;

/**
 * Factory for creating Oryx ORM EntityManager instances.
 */
class EntityManagerFactory
{
    private static ?EntityManager $instance = null;

    /**
     * Get shared EntityManager instance (singleton).
     */
    public static function getInstance(): EntityManager
    {
        if (self::$instance === null) {
            self::$instance = self::createFromEnv();
        }

        return self::$instance;
    }

    /**
     * Reset the singleton instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

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
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $connection = DriverManager::getConnection($connectionParams);
        return new EntityManager($connection, [
            'metadata.schema_path' => $schemaPath,
            'metadata.auto_generate_proxy' => $autoGenerateProxy,
            'metadata.proxy_dir' => $proxyDir,
            'metadata.proxy_namespace' => $proxyNamespace,
        ]);
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
