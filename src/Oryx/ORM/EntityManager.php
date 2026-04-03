<?php

declare(strict_types=1);

namespace Oryx\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Doctrine\UuidType;
use League\Event\Emitter;
use League\Event\Event;
use League\Event\EmitterInterface;

class EntityManager implements EntityManagerInterface
{
    private DoctrineEntityManager $em;
    private EmitterInterface $eventDispatcher;

    public function __construct(Connection $connection, array $config = [], ?EmitterInterface $eventDispatcher = null)
    {
        $doctrineConfig = new Configuration();

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $schemaPath = $config['metadata.schema_path'] ?? dirname(__DIR__, 3) . '/schema';
        $driver = $config['metadata.driver'] ?? new SimplifiedXmlDriver([
            $schemaPath => 'App\Entity',
        ], '.orm.xml');
        $doctrineConfig->setMetadataDriverImpl($driver);

        // Proxy configuration
        $autoGenerate = $config['metadata.auto_generate_proxy'] ?? ProxyFactory::AUTOGENERATE_NEVER;
        $doctrineConfig->setAutoGenerateProxyClasses($autoGenerate);
        $doctrineConfig->setProxyDir($config['metadata.proxy_dir'] ?? sys_get_temp_dir());
        $doctrineConfig->setProxyNamespace($config['metadata.proxy_namespace'] ?? 'Oryx\ORM\Proxy');

        $this->em = DoctrineEntityManager::create($connection, $doctrineConfig);
        $this->eventDispatcher = $eventDispatcher ?? new Emitter();
    }

    public function getDoctrineEntityManager(): DoctrineEntityManager
    {
        return $this->em;
    }

    public function getRepository($className): EntityRepository
    {
        return $this->em->getRepository($className);
    }

    public function persist($entity): void
    {
        // Dispatch prePersist event
        $this->eventDispatcher->emit(new Event('orm.prePersist', [
            'entity' => $entity,
            'entityManager' => $this,
        ]));

        $this->em->persist($entity);

        // Dispatch postPersist event
        $this->eventDispatcher->emit(new Event('orm.postPersist', [
            'entity' => $entity,
            'entityManager' => $this,
        ]));
    }

    public function flush(): void
    {
        // Dispatch preFlush event
        $this->eventDispatcher->emit(new Event('orm.preFlush', [
            'entityManager' => $this,
        ]));

        $this->em->flush();

        // Dispatch postFlush event
        $this->eventDispatcher->emit(new Event('orm.postFlush', [
            'entityManager' => $this,
        ]));
    }

    public function clear($entityName = null): void
    {
        // Dispatch preClear event
        $this->eventDispatcher->emit(new Event('orm.preClear', [
            'entityName' => $entityName,
            'entityManager' => $this,
        ]));

        $this->em->clear($entityName);

        // Dispatch postClear event
        $this->eventDispatcher->emit(new Event('orm.postClear', [
            'entityName' => $entityName,
            'entityManager' => $this,
        ]));
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->em->createQueryBuilder());
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return new UnitOfWork($this->em);
    }

    public function getEventDispatcher(): EmitterInterface
    {
        return $this->eventDispatcher;
    }

    public function isOpen(): bool
    {
        return $this->em->isOpen();
    }

    public function close(): void
    {
        $this->em->close();
    }

    public function getConnection(): Connection
    {
        return $this->em->getConnection();
    }

    public function find(string $entityName, $id): ?object
    {
        if (is_string($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $id = \Ramsey\Uuid\Uuid::fromString($id);
        }
        return $this->em->find($entityName, $id);
    }

    public function remove($entity): void
    {
        $this->em->remove($entity);
    }

    public function refresh($entity, ?int $lockMode = null): void
    {
        $this->em->refresh($entity, $lockMode);
    }

    public function detach($entity): void
    {
        $this->em->detach($entity);
    }

    public function merge($entity): object
    {
        return $this->em->merge($entity);
    }

    public function contains($entity): bool
    {
        return $this->em->contains($entity);
    }

    public function getClassMetadata($className): \Doctrine\ORM\Mapping\ClassMetadata
    {
        return $this->em->getClassMetadata($className);
    }

    public function getMetadataFactory(): \Doctrine\Persistence\Mapping\ClassMetadataFactory
    {
        return $this->em->getMetadataFactory();
    }

    public function initializeObject($obj): void
    {
        $this->em->initializeObject($obj);
    }

    public function isUninitializedObject($val): bool
    {
        return $this->em->isUninitializedObject($val);
    }

    public function getCache(): ?\Doctrine\ORM\Cache
    {
        return $this->em->getCache();
    }

    public function getExpressionBuilder(): \Doctrine\ORM\Query\Expr
    {
        return $this->em->getExpressionBuilder();
    }

    public function beginTransaction(): void
    {
        $this->em->beginTransaction();
    }

    public function transactional($func): mixed
    {
        return $this->em->transactional($func);
    }

    public function commit(): void
    {
        $this->em->commit();
    }

    public function rollback(): void
    {
        $this->em->rollback();
    }

    public function createQuery($dql = ''): \Doctrine\ORM\Query
    {
        return $this->em->createQuery($dql);
    }

    public function createNamedQuery($name): \Doctrine\ORM\Query
    {
        return $this->em->createNamedQuery($name);
    }

    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm): \Doctrine\ORM\NativeQuery
    {
        return $this->em->createNativeQuery($sql, $rsm);
    }

    public function createNamedNativeQuery($name): \Doctrine\ORM\NativeQuery
    {
        return $this->em->createNamedNativeQuery($name);
    }

    public function getReference($entityName, $id): ?object
    {
        return $this->em->getReference($entityName, $id);
    }

    public function getPartialReference($entityName, $identifier): ?object
    {
        return $this->em->getPartialReference($entityName, $identifier);
    }

    public function copy($entity, $deep = false): object
    {
        return $this->em->copy($entity, $deep);
    }

    public function lock($entity, $lockMode, $lockVersion = null): void
    {
        $this->em->lock($entity, $lockMode, $lockVersion);
    }

    public function getEventManager(): \Doctrine\Common\EventManager
    {
        return $this->em->getEventManager();
    }

    public function getConfiguration(): \Doctrine\ORM\Configuration
    {
        return $this->em->getConfiguration();
    }

    public function getHydrator($hydrationMode): \Doctrine\ORM\Internal\Hydration\AbstractHydrator
    {
        return $this->em->getHydrator($hydrationMode);
    }

    public function newHydrator($hydrationMode): \Doctrine\ORM\Internal\Hydration\AbstractHydrator
    {
        return $this->em->newHydrator($hydrationMode);
    }

    public function getProxyFactory(): \Doctrine\ORM\Proxy\ProxyFactory
    {
        return $this->em->getProxyFactory();
    }

    public function getFilters(): \Doctrine\ORM\Query\FilterCollection
    {
        return $this->em->getFilters();
    }

    public function isFiltersStateClean(): bool
    {
        return $this->em->isFiltersStateClean();
    }

    public function hasFilters(): bool
    {
        return $this->em->hasFilters();
    }
}
