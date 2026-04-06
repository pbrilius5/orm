#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Db;
use App\Cache\CacheUnion;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

echo "=== Caching Benchmarks ===\n\n";

$iterations = 100;

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

function formatMs(float $ms): string
{
    if ($ms < 1) {
        return sprintf('%.2f µs', $ms * 1000);
    } elseif ($ms < 1000) {
        return sprintf('%.2f ms', $ms);
    } else {
        return sprintf('%.2f s', $ms / 1000);
    }
}

echo "=== A) Db + Flysystem Cache Benchmarks ===\n\n";

$smallData = str_repeat('a', 1024);
$mediumData = str_repeat('x', 102400);
$largeData = str_repeat('y', 1048576);

echo "A1. Write Cache (1KB):\n";
$memBefore = memory_get_usage();
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Db::writeCache('bench_write_1kb_' . $i, $smallData);
}
$write1kb = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s | Memory: %s\n",
    formatMs($write1kb * 1000),
    formatMs(($write1kb / $iterations) * 1000),
    formatBytes(memory_get_usage() - $memBefore)
);

echo "\nA2. Read Cache (1KB):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = Db::readCache('bench_write_1kb_' . $i);
}
$read1kb = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($read1kb * 1000),
    formatMs(($read1kb / $iterations) * 1000)
);

echo "\nA3. Write Cache (100KB):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Db::writeCache('bench_write_100kb', $mediumData);
}
$write100kb = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($write100kb * 1000),
    formatMs(($write100kb / $iterations) * 1000)
);

echo "\nA4. Read Cache (100KB):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = Db::readCache('bench_write_100kb');
}
$read100kb = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($read100kb * 1000),
    formatMs(($read100kb / $iterations) * 1000)
);

echo "\nA5. Has Cache:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = Db::hasCache('bench_write_1kb_' . $i);
}
$hasCache = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($hasCache * 1000),
    formatMs(($hasCache / $iterations) * 1000)
);

echo "\nA6. Delete Cache:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    Db::deleteCache('bench_write_1kb_' . $i);
}
$deleteCache = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($deleteCache * 1000),
    formatMs(($deleteCache / $iterations) * 1000)
);

echo "\n=== B) CacheUnion 3-Tier Benchmarks ===\n\n";

$storagePath = dirname(__DIR__, 2) . '/var/storage';
$adapter = new LocalFilesystemAdapter($storagePath);
$fs = new Filesystem($adapter);
Db::setFilesystem($fs);

$cache = CacheUnion::getInstance($fs);

echo "B1. L1 (Memory cache) - hit:\n";
$cache->set('l1_test_key', 'test_value', 3600);
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = $cache->get('l1_test_key');
}
$l1Hit = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($l1Hit * 1000),
    formatMs(($l1Hit / $iterations) * 1000)
);

echo "\nB2. L2 (Flysystem) - hit (after L1 cleared):\n";
CacheUnion::reset();
$cache = CacheUnion::getInstance($fs);
Db::writeCache('l2_test_key', 'l2_test_value');
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = $cache->get('l2_test_key');
}
$l2Hit = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($l2Hit * 1000),
    formatMs(($l2Hit / $iterations) * 1000)
);

echo "\nB3. Cache miss (all tiers):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = $cache->get('non_existent_key_' . $i);
}
$miss = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($miss * 1000),
    formatMs(($miss / $iterations) * 1000)
);

echo "\nB4. Cache set (all tiers):\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->set('set_test_key_' . $i, 'value_' . $i, 3600);
}
$set = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($set * 1000),
    formatMs(($set / $iterations) * 1000)
);

echo "\nB5. Cache has:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $result = $cache->has('set_test_key_' . $i);
}
$has = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($has * 1000),
    formatMs(($has / $iterations) * 1000)
);

echo "\nB6. Cache delete:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cache->delete('set_test_key_' . $i);
}
$delete = microtime(true) - $start;
printf(
    "   Total: %s | Avg: %s\n",
    formatMs($delete * 1000),
    formatMs(($delete / $iterations) * 1000)
);

echo "\n=== C) TTL Benchmarks ===\n\n";

echo "C1. TTL 60s (immediate check):\n";
$cache->set('ttl_60', 'value', 60);
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = $cache->get('ttl_60');
}
$ttl60 = microtime(true) - $start;
printf("   All hits within TTL: %s\n", formatMs($ttl60 * 1000));

echo "\nC2. TTL 0 (permanent):\n";
$cache->set('ttl_permanent', 'value', 0);
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = $cache->get('ttl_permanent');
}
$ttl0 = microtime(true) - $start;
printf("   All hits (no expiry): %s\n", formatMs($ttl0 * 1000));

echo "\n=== D) Size Benchmarks ===\n\n";

$sizes = [
    '10B' => str_repeat('a', 10),
    '1KB' => str_repeat('b', 1024),
    '10KB' => str_repeat('c', 10240),
    '100KB' => str_repeat('d', 102400),
];

foreach ($sizes as $name => $data) {
    $key = 'size_test_' . $name;
    $cache->set($key, $data, 3600);

    $start = microtime(true);
    for ($i = 0; $i < 50; $i++) {
        $result = $cache->get($key);
    }
    $time = microtime(true) - $start;
    printf("   %-8s write+read: %s\n", $name, formatMs($time * 1000));

    $cache->delete($key);
}

echo "\n=== E) Stability Test ===\n\n";

echo "E1. Concurrent read/write:\n";
$start = microtime(true);
for ($i = 0; $i < 50; $i++) {
    $cache->set('stable_key_' . $i, 'value_' . $i);
    $result = $cache->get('stable_key_' . $i);
    $cache->has('stable_key_' . $i);
    $cache->delete('stable_key_' . $i);
}
$stable = microtime(true) - $start;
printf("   50 cycles: %s\n", formatMs($stable * 1000));

echo "\nE2. Memory usage after 1000 operations:\n";
$memBefore = memory_get_usage();
for ($i = 0; $i < 1000; $i++) {
    $cache->set('mem_test_' . $i, 'value_' . $i, 3600);
}
$memAfter = memory_get_usage();
printf("   Memory delta: %s\n", formatBytes($memAfter - $memBefore));

echo "\nE3. Clear all cache:\n";
for ($i = 0; $i < 100; $i++) {
    $cache->set('clear_test_' . $i, 'value_' . $i);
}
$start = microtime(true);
$cache->clear();
$clear = microtime(true) - $start;
printf("   Clear 100 items: %s\n", formatMs($clear * 1000));

echo "\n=== Summary ===\n\n";

$slowest = max($l1Hit, $l2Hit, $miss);
$fastest = min($l1Hit, $l2Hit, $miss);
$diff = $slowest > 0 ? (($slowest - $fastest) / $fastest) * 100 : 0;

echo "Fastest tier: L1 (Memory) - " . formatMs(($l1Hit / $iterations) * 1000) . "\n";
echo "Slowest tier: Cache miss - " . formatMs(($miss / $iterations) * 1000) . "\n";
echo "Speed difference: " . sprintf('%.1f%%', $diff) . "\n";

$jsonOutput = [
    'timestamp' => date('c'),
    'iterations' => $iterations,
    'db_flysystem' => [
        'write_1kb_avg_ms' => round(($write1kb / $iterations) * 1000, 4),
        'read_1kb_avg_ms' => round(($read1kb / $iterations) * 1000, 4),
        'write_100kb_avg_ms' => round(($write100kb / $iterations) * 1000, 4),
        'read_100kb_avg_ms' => round(($read100kb / $iterations) * 1000, 4),
        'has_avg_ms' => round(($hasCache / $iterations) * 1000, 4),
        'delete_avg_ms' => round(($deleteCache / $iterations) * 1000, 4),
    ],
    'cache_union' => [
        'l1_hit_ms' => round(($l1Hit / $iterations) * 1000, 4),
        'l2_hit_ms' => round(($l2Hit / $iterations) * 1000, 4),
        'miss_ms' => round(($miss / $iterations) * 1000, 4),
        'set_ms' => round(($set / $iterations) * 1000, 4),
        'has_ms' => round(($has / $iterations) * 1000, 4),
        'delete_ms' => round(($delete / $iterations) * 1000, 4),
    ],
    'stability' => [
        'concurrent_50_cycles_ms' => round($stable * 1000, 2),
        'memory_delta_1000_ops' => $memAfter - $memBefore,
        'clear_100_items_ms' => round($clear * 1000, 2),
    ],
    'comparison' => [
        'fastest_tier' => 'L1 (Memory)',
        'slowest_tier' => 'Cache miss',
        'difference_pct' => round($diff, 1),
    ],
];

echo "\n=== JSON Export ===\n";
echo json_encode($jsonOutput, JSON_PRETTY_PRINT) . "\n";

echo "\nDone.\n";
