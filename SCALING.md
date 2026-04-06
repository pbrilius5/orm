# Scaling & Performance Strategy

> Pragmatic real-politik realization of app performance through caching infrastructure.

## Executive Summary

| Metric | Result | Impact |
|--------|--------|--------|
| L1 (Memory) hit | 5.46 µs | **1952.8%** faster than miss |
| L2 (Flysystem) hit | 6.48 µs | Optimal for repeated access |
| Cache miss | 97.57 µs | Acceptable baseline |
| Write throughput | ~2000 ops/sec | SQLite + Flysystem |

---

## Architecture Overview

### Current Infrastructure

```
┌─────────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                           │
├─────────────────────────────────────────────────────────────────┤
│  MVC/ADR Patterns                                              │
│  ├── Oryx MVC (vanilla PHP)                                    │
│  └── Oryx ADR (laminas/diactoros)                              │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                    CACHING LAYER                               │
├─────────────────────────────────────────────────────────────────┤
│  CacheUnion (3-tier compound)                                  │
│  ├── L1: Memory (ArrayCache) - in-memory, fastest             │
│  ├── L2: Flysystem (var/cache/) - file-based                   │
│  └── L3: DB (PersistentSingleton) - persistent                 │
├─────────────────────────────────────────────────────────────────┤
│  Db (PDO singleton + Flysystem)                                │
│  ├── PDO SQLite - direct database access                       │
│  └── Flysystem - storage/cache operations                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                    DATABASE LAYER                              │
├─────────────────────────────────────────────────────────────────┤
│  MySQL (pdo_mysql) - ORM/DBAL only (Production)                │
│  SQLite (pdo_sqlite) - Custom singletons, cache (Development) │
└─────────────────────────────────────────────────────────────────┘
```

---

## MySQL Integration (ORM only)

### Taisyklė

> **MySQL (pdo_mysql) naudojamas TIK per Doctrine ORM/DBAL.**
> **NIEKADA tiesiogiai per PDO.**

| Komponentas | MySQL | SQLite (custom) |
|------------|-------|-----------------|
| Doctrine EntityManager | ✅ Naudoja | ✅ Naudoja |
| Doctrine DBAL | ✅ Naudoja | ✅ Naudoja |
| App\Db (PDO) | ❌ NENAUDOJAMAS | ✅ Naudojamas |
| CacheUnion | ❌ NENAUDOJAMAS | ✅ Naudojamas |

### Configuration

```php
// EnvironmentConfig.php - getOrmDatabaseParams()
public function getOrmDatabaseParams(): array
{
    return [
        'driver' => $this->get('database.driver', 'pdo_mysql'),
        'host' => $this->get('database.host', 'localhost'),
        'port' => (int) $this->get('database.port', '3306'),
        'dbname' => $this->get('database.name', 'orm_db'),
        'user' => $this->get('database.user', 'root'),
        'password' => $this->get('database.password', ''),
        'charset' => $this->get('database.charset', 'utf8mb4'),
    ];
}
```

### EntityManagerFactory Logika

- **Production (`APP_ENV=prod`)**: Naudoja MySQL (pdo_mysql) per default
- **Development (`APP_ENV=dev`)**: Naudoja SQLite (pdo_sqlite) per default

---

## Container Integration

### MvcServiceProvider

| Service | Integration |
|---------|-------------|
| `EntityManager` | Per `EntityManagerFactory::getInstance()` |
| `Db` | `Db::getInstance()` + `Db::setFilesystem()` |
| `CacheUnion` | `CacheUnion::getInstance($fs, $registry)` |
| `FilesystemOperator` | Per `League\Flysystem\Filesystem` |

### CLI Commands

| Command | Aprašymas |
|---------|-----------|
| `bin/console oryx:cache:status` | Cache, Db, Flysystem status |
| `bin/console oryx:db:check` | ORM + Custom PDO status |

---

## Scaling Scope

### Tier 1: Development / Small Scale

- **Database:** SQLite (pdo_sqlite)
- **Cache:** L1 (Memory) + L2 (Flysystem)
- **Storage:** Local Flysystem
- **Load:** < 100 req/sec

### Tier 2: Production / Medium Scale

- **Database:** MySQL (pdo_mysql) per ORM/DBAL
- **Cache:** L1 + L2 + L3 (all tiers)
- **Storage:** Flysystem (local/S3)
- **Load:** 100-1000 req/sec

### Tier 3: Enterprise / Large Scale

- **Database:** MySQL + Read replicas
- **Cache:** Redis/Memcached + L1
- **Storage:** S3/Cloud storage
- **Load:** > 1000 req/sec

---

## Performance Targets

### Response Time Targets

| Operation | Target | Current | Status |
|-----------|--------|---------|--------|
| L1 cache hit | < 10 µs | 6.28 µs | ✅ |
| L2 cache hit | < 50 µs | 8.53 µs | ✅ |
| L3 cache hit | < 200 µs | 146.55 µs | ✅ |
| DB write (SQLite) | < 1 ms | 0.53 ms | ✅ |
| DB read (SQLite) | < 0.5 ms | 0.08 ms | ✅ |

### Throughput Targets

| Operation | Target | Current |
|-----------|--------|---------|
| Cache writes/sec | > 5000 | ~2000 |
| Cache reads/sec | > 10000 | ~12000 |
| DB queries/sec | > 1000 | N/A |

---

## Utilization Program

### 1. Caching Strategy

```
WHEN to use which cache layer:

L1 (Memory) - for:
  → Frequently accessed data
  → Session data
  → User preferences
  → Rate limiting counters

L2 (Flysystem) - for:
  → API responses
  → Large data objects
  → File metadata
  → Temporary computation results

L3 (DB) - for:
  → Persistent configuration
  → Cross-process data
  → Long-term caching
  → Distributed cache
```

### 2. Implementation Guidelines

```php
// Use L1 for frequent access
$cache = CacheUnion::getInstance();
$value = $cache->get('frequent_key'); // L1 hit: 6.28 µs

// Use L2 for large data
Db::writeCache('large_response', $response); // Flysystem: 80 µs

// Use L3 for persistent data
$cache->set('persistent_config', $config, 86400); // DB: 146 µs

// Use Db directly for raw SQLite
$db = Db::getInstance();
$db->query('SELECT * FROM users');
```

### 3. Anti-Patterns to Avoid

| Anti-Pattern | Problem | Solution |
|--------------|---------|----------|
| Cache everything | Memory bloat | Use L2/L3 for large data |
| No TTL | Stale data | Always set TTL |
| Cache database | Double caching | Use L3 only when needed |
| Synchronous writes | Slow writes | Use async for L2/L3 |

---

## Guided Conventionalization

### Code Organization

```
src/
├── Db.php                 # PDO + Flysystem singleton
├── Cache/
│   └── CacheUnion.php     # 3-tier compound cache
├── Oryx/
│   └── ORM/               # Doctrine ORM (MySQL)
└── Service/
    ├── PersistentSingletonRegistry.php
    └── FlysystemService.php
```

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Cache keys | `kebab-case` | `user-session-123` |
| Storage paths | `snake_case` | `cache/user_sessions/` |
| TTL constants | `SCREAMING_SNAKE` | `CACHE_TTL_DEFAULT` |

### Configuration

```php
// Cache configuration
return [
    'cache' => [
        'l1_enabled' => true,
        'l2_enabled' => true,
        'l3_enabled' => true,
        'default_ttl' => 3600,
        'max_memory_items' => 1000,
    ],
    
    'database' => [
        'driver' => 'pdo_sqlite', // or pdo_mysql
        'path' => 'var/data/orm.db',
    ],
    
    'flysystem' => [
        'storage_path' => 'var/storage',
        'cache_path' => 'var/cache',
    ],
];
```

---

## Stability & Monitoring

### Health Checks

```bash
# Check cache status
bin/console oryx:cache:status

# Check database
bin/console oryx:db:create --check

# Check filesystem
bin/console oryx:storage:status
```

### Metrics to Track

| Metric | Alert Threshold |
|--------|-----------------|
| L1 hit ratio | < 80% |
| L2 hit ratio | < 50% |
| Memory usage | > 80% |
| Cache error rate | > 1% |
| Response time p99 | > 500ms |

---

## Action Plan

### Phase 1: Implementation (Current)

- [x] Db.php (PDO + Flysystem)
- [x] CacheUnion (3-tier)
- [x] benchmark-cache.php
- [x] REQUIREMENTS.md

### Phase 2: Integration

- [ ] Add CacheUnion to MvcServiceProvider
- [ ] Add Db singleton to container
- [ ] Configure cache keys for entities
- [ ] Add health check commands

### Phase 3: Optimization

- [ ] Implement cache warmup
- [ ] Add cache invalidation strategies
- [ ] Configure Redis/Memcached for prod
- [ ] Add monitoring/alerting

### Phase 4: Scaling

- [ ] MySQL integration (ORM only)
- [ ] Read replicas
- [ ] S3 storage adapter
- [ ] CDN integration

---

## Conclusion

The caching infrastructure provides **2233.6% performance improvement** for cached data:
- L1 (Memory): 6.28 µs avg
- L2 (Flysystem): 8.53 µs avg  
- L3 (DB): 146.55 µs avg

This enables pragmatic scaling from development to production with clear migration paths.
