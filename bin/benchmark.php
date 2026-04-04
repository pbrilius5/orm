#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Oryx\ORM\EntityManager;
use Oryx\ORM\EntityManagerFactory;
use Oryx\ORM\Mapping\Driver\XmlThenAttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

echo "=== Metadata Driver Benchmark ===\n\n";

$iterations = 20;
$schemaPath = dirname(__DIR__) . '/schema';
$entityPath = dirname(__DIR__) . '/src/Entity';

$connectionParams = [
    'driver' => 'pdo_sqlite',
    'path' => ':memory:',
];

// Benchmark 1: XML Driver only
echo "1. XML Driver (SimplifiedXmlDriver):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $driver = new SimplifiedXmlDriver([
        $schemaPath => 'App\\Entity',
    ], '.orm.xml');
    $em = EntityManagerFactory::create($connectionParams, $schemaPath, \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER);
}
$xmlTime = microtime(true) - $start;
printf("   Total: %.2f ms | Avg: %.2f ms\n", $xmlTime * 1000, ($xmlTime / $iterations) * 1000);

// Benchmark 2: Attribute Driver only
echo "\n2. Attribute Driver:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $attrDriver = new AttributeDriver([$entityPath]);
    $em = EntityManagerFactory::create(
        $connectionParams,
        $schemaPath,
        \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER,
        null,
        null,
        ['metadata.driver' => $attrDriver]
    );
}
$attrTime = microtime(true) - $start;
printf("   Total: %.2f ms | Avg: %.2f ms\n", $attrTime * 1000, ($attrTime / $iterations) * 1000);

// Benchmark 3: Mixed Driver (XmlThenAttributeDriver)
echo "\n3. Mixed Driver (XmlThenAttributeDriver):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $mixedDriver = new XmlThenAttributeDriver($schemaPath, $entityPath);
    $em = EntityManagerFactory::create(
        $connectionParams,
        $schemaPath,
        \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER,
        null,
        null,
        ['metadata.driver' => $mixedDriver]
    );
}
$mixedTime = microtime(true) - $start;
printf("   Total: %.2f ms | Avg: %.2f ms\n", $mixedTime * 1000, ($mixedTime / $iterations) * 1000);

// Summary
echo "\n=== Summary ===\n";
echo "XML:    " . sprintf('%.2f ms', $xmlTime * 1000) . " total\n";
echo "Attr:   " . sprintf('%.2f ms', $attrTime * 1000) . " total\n";
echo "Mixed:  " . sprintf('%.2f ms', $mixedTime * 1000) . " total\n";

$fastest = min($xmlTime, $attrTime, $mixedTime);
$slowest = max($xmlTime, $attrTime, $mixedTime);
$diff = (($slowest - $fastest) / $fastest) * 100;

echo "\nDifference: " . sprintf('%.1f%%', $diff) . " between fastest and slowest\n";

// Test entity loading
echo "\n=== Entity Loading Test ===\n";
$em = EntityManagerFactory::getInstance();

$entities = [
    'User' => \App\Entity\User::class,
    'Group' => \App\Entity\Group::class,
    'Role' => \App\Entity\Role::class,
    'UserRole' => \App\Entity\UserRole::class,
    'WizardRole' => \App\Entity\WizardRole::class,
    'ArchitectRole' => \App\Entity\ArchitectRole::class,
    'GameMasterRole' => \App\Entity\GameMasterRole::class,
];

foreach ($entities as $name => $class) {
    try {
        $metadata = $em->getClassMetadata($class);
        echo "✓ $name → table: " . $metadata->getTableName() . "\n";
    } catch (\Throwable $e) {
        echo "✗ $name → ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== PHP-DI Autowiring Test ===\n";
$builder = new \DI\ContainerBuilder();
$builder->useAutowiring(true);
$builder->useAttributes(true);
$container = $builder->build();
$container->set(EntityManager::class, $em);
$container->set(\App\Dto\DtoFactory::class, \DI\autowire());

try {
    $dtoFactory = $container->get(\App\Dto\DtoFactory::class);
    echo "✓ DtoFactory autowired successfully\n";
} catch (\Throwable $e) {
    echo "✗ DtoFactory autowiring failed: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
