<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Oryx\ORM\EntityManager;
use Oryx\ORM\UnitOfWork;
use Ramsey\Uuid\Doctrine\UuidType;

class UnitOfWorkTest extends TestCase
{
    private EntityManager $entityManager;
    private UnitOfWork $unitOfWork;

    protected function setUp(): void
    {
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $conn = DriverManager::getConnection($connectionParams);
        $config = new Configuration();

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $schemaPath = dirname(__DIR__, 2) . '/schema';
        $driver = new SimplifiedXmlDriver([
            $schemaPath => 'App\Entity',
        ], '.orm.xml');
        $config->setMetadataDriverImpl($driver);
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_ALWAYS);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Oryx\ORM\Proxy');

        $this->entityManager = new EntityManager($conn);
        $this->unitOfWork = $this->entityManager->getUnitOfWork();
    }

    public function testUnitOfWorkIsCreated(): void
    {
        $this->assertInstanceOf(UnitOfWork::class, $this->unitOfWork);
        $this->assertInstanceOf(\Doctrine\ORM\EntityManager::class, $this->unitOfWork->getEntityManager());
    }

    public function testScheduleForInsert(): void
    {
        $entity = new \stdClass();
        $this->unitOfWork->scheduleForInsert($entity);
        $this->assertTrue($this->unitOfWork->isScheduledForInsert($entity));
    }

    public function testScheduleForUpdate(): void
    {
        $this->expectException(\Doctrine\ORM\ORMInvalidArgumentException::class);
        $entity = new \stdClass();
        $this->unitOfWork->scheduleForUpdate($entity);
    }

    public function testScheduleForDelete(): void
    {
        $entity = new \stdClass();
        $this->unitOfWork->scheduleForDelete($entity);
        $this->assertTrue($this->unitOfWork->isScheduledForDeletion($entity));
    }
}
