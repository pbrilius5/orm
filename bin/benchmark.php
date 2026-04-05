#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Oryx\ORM\EntityManager;
use Oryx\ORM\EntityManagerFactory;
use Oryx\ORM\Mapping\Driver\XmlThenAttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use App\Dto\DtoFactory;
use App\Entity\User;
use App\Entity\Group;
use App\Entity\DeveloperGroup;
use App\Entity\DesignerGroup;
use App\Entity\TesterGroup;
use App\Entity\Role;
use App\Entity\WizardRole;
use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\UserRole;
use App\Entity\UserGroup;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

echo "=== Chromatic Lighthouse Benchmark ===\n\n";

$iterations = 20;
$schemaPath = dirname(__DIR__) . '/schema';
$entityPath = dirname(__DIR__) . '/src/Entity';

$connectionParams = [
    'driver' => 'pdo_sqlite',
    'path' => ':memory:',
];

function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1048576) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
}

// Benchmark 1: XML Driver
echo "1. XML Driver (SimplifiedXmlDriver):\n";
$memBefore = memory_get_usage();
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Oryx\ORM\EntityManagerFactory::reset();
    $em = EntityManagerFactory::create($connectionParams, $schemaPath, \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER);
}
$xmlTime = microtime(true) - $start;
$xmlMem = memory_get_usage() - $memBefore;
printf("   Total: %.2f ms | Avg: %.2f ms | Memory: %s\n", $xmlTime * 1000, ($xmlTime / $iterations) * 1000, formatBytes($xmlMem));

// Benchmark 2: Attribute Driver
echo "\n2. Attribute Driver:\n";
$memBefore = memory_get_usage();
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Oryx\ORM\EntityManagerFactory::reset();
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
$attrMem = memory_get_usage() - $memBefore;
printf("   Total: %.2f ms | Avg: %.2f ms | Memory: %s\n", $attrTime * 1000, ($attrTime / $iterations) * 1000, formatBytes($attrMem));

// Benchmark 3: Mixed Driver
echo "\n3. Mixed Driver (XmlThenAttributeDriver):\n";
$memBefore = memory_get_usage();
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Oryx\ORM\EntityManagerFactory::reset();
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
$mixedMem = memory_get_usage() - $memBefore;
printf("   Total: %.2f ms | Avg: %.2f ms | Memory: %s\n", $mixedTime * 1000, ($mixedTime / $iterations) * 1000, formatBytes($mixedMem));

// DTO Conversion Benchmark
echo "\n=== DTO Conversion Benchmark ===\n";
Oryx\ORM\EntityManagerFactory::reset();
$em = EntityManagerFactory::getInstance();

$builder = new \DI\ContainerBuilder();
$builder->useAutowiring(true);
$builder->useAttributes(true);
$container = $builder->build();
$container->set(EntityManager::class, $em);
$container->set(DtoFactory::class, \DI\autowire());
$dtoFactory = $container->get(DtoFactory::class);

$entityTypes = [
    'User' => User::class,
    'UserGroup' => UserGroup::class,
    'Group' => Group::class,
    'DeveloperGroup' => DeveloperGroup::class,
    'DesignerGroup' => DesignerGroup::class,
    'TesterGroup' => TesterGroup::class,
    'Role' => Role::class,
    'UserRole' => UserRole::class,
    'WizardRole' => WizardRole::class,
    'ArchitectRole' => ArchitectRole::class,
    'GameMasterRole' => GameMasterRole::class,
];

foreach ($entityTypes as $name => $class) {
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $metadata = $em->getClassMetadata($class);
    }
    $time = microtime(true) - $start;
    printf("   %-20s → table: %-15s | Avg: %.3f ms\n", $name, $metadata->getTableName(), ($time / $iterations) * 1000);
}

// Entity Loading Test
echo "\n=== Entity Loading Test ===\n";
foreach ($entityTypes as $name => $class) {
    try {
        $metadata = $em->getClassMetadata($class);
        echo "✓ $name → table: " . $metadata->getTableName() . "\n";
    } catch (\Throwable $e) {
        echo "✗ $name → ERROR: " . $e->getMessage() . "\n";
    }
}

// PHP-DI Autowiring Test
echo "\n=== PHP-DI Autowiring Test ===\n";
try {
    $dtoFactory = $container->get(DtoFactory::class);
    echo "✓ DtoFactory autowired successfully\n";
} catch (\Throwable $e) {
    echo "✗ DtoFactory autowiring failed: " . $e->getMessage() . "\n";
}

// Summary
echo "\n=== Summary ===\n";
echo "XML:    " . sprintf('%.2f ms', $xmlTime * 1000) . " total | " . formatBytes($xmlMem) . "\n";
echo "Attr:   " . sprintf('%.2f ms', $attrTime * 1000) . " total | " . formatBytes($attrMem) . "\n";
echo "Mixed:  " . sprintf('%.2f ms', $mixedTime * 1000) . " total | " . formatBytes($mixedMem) . "\n";

$fastest = min($xmlTime, $attrTime, $mixedTime);
$slowest = max($xmlTime, $attrTime, $mixedTime);
$diff = (($slowest - $fastest) / $fastest) * 100;

echo "\nDifference: " . sprintf('%.1f%%', $diff) . " between fastest and slowest\n";

// JSON export
$jsonOutput = [
    'timestamp' => date('c'),
    'iterations' => $iterations,
    'drivers' => [
        'xml' => [
            'total_ms' => round($xmlTime * 1000, 2),
            'avg_ms' => round(($xmlTime / $iterations) * 1000, 2),
            'memory' => $xmlMem,
        ],
        'attribute' => [
            'total_ms' => round($attrTime * 1000, 2),
            'avg_ms' => round(($attrTime / $iterations) * 1000, 2),
            'memory' => $attrMem,
        ],
        'mixed' => [
            'total_ms' => round($mixedTime * 1000, 2),
            'avg_ms' => round(($mixedTime / $iterations) * 1000, 2),
            'memory' => $mixedMem,
        ],
    ],
    'fastest' => $fastest < $xmlTime ? ($fastest < $attrTime ? 'mixed' : 'attribute') : 'xml',
    'difference_pct' => round($diff, 1),
];

echo "\n=== JSON Export ===\n";
echo json_encode($jsonOutput, JSON_PRETTY_PRINT) . "\n";

echo "\nDone.\n";
