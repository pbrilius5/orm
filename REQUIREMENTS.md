# Requirements

## System Requirements

### PHP
- **PHP 8.2+**

### Required Extensions
| Extension | Purpose |
|-----------|---------|
| `mbstring` | Multibyte string handling |
| `intl` | Internationalization |
| `pdo_sqlite` | SQLite driver (custom singletons) |
| `pdo_mysql` | MySQL driver (ORM/DBAL only) |
| `sodium` | Cryptographically secure UUID generation |
| `json` | JSON encoding/decoding (enabled by default) |

### Optional Extensions
| Extension | Purpose |
|-----------|---------|
| `memcached` | Development cache server (APP_ENV=dev) |
| `redis` | Production cache server (APP_ENV=prod) |

## Dependencies

### Core
| Package | Purpose |
|---------|---------|
| `doctrine/dbal ^3.0` | Database abstraction layer |
| `doctrine/orm ^2.14` | Object-relational mapping |
| `doctrine/migrations ^3.5` | Database schema migrations |
| `ramsey/uuid-doctrine ^2.1` | UUID type mapping + libsodium-based ID generation |

### HTTP / MVC
| Package | Purpose |
|---------|---------|
| `oryx/mvc` | MVC framework |
| `oryx/adr` | ADR pattern support |
| `laminas/laminas-diactoros` | PSR-7 HTTP messages |

### Forms & Validation
| Package | Purpose |
|---------|---------|
| `laminas/laminas-form ^2.6` | Form rendering |
| `laminas/laminas-inputfilter ^2.5` | Input filtering |
| `laminas/laminas-validator ^2.5` | Data validation |
| `laminas/laminas-filter ^2.5` | Input filtering |

### API
| Package | Purpose |
|---------|---------|
| `league/fractal ^0.21` | Resource transformation (HAL+JSON) |
| `league/pipeline ^1.0` | Pipeline pattern |

### Storage
| Package | Purpose |
|---------|---------|
| `league/flysystem ^3.0` | File storage (cache layer) |

### Development
| Package | Purpose |
|---------|---------|
| `phpunit/phpunit ^9.5` | Unit testing |
| `mockery/mockery ^1.0` | Mock objects |
| `friendsofphp/php-cs-fixer ^3.0` | Code style |
| `phpstan/phpstan ^0.12` | Static analysis |
| `fakerphp/faker ^1.23` | Fake data generation |
| `league/factory-muffin ^3.0` | Test fixtures |

## UUID Generation

IDs use **libsodium** (`ext-sodium`) for cryptographically secure random byte generation via `Ramsey\Uuid\Uuid::uuid4()`.

- Generator: `Oryx\ORM\SodiumUuidGenerator` (extends `Doctrine\ORM\Id\AbstractIdGenerator`)
- Type: `uuid` (registered via `Ramsey\Uuid\Doctrine\UuidType`)
- Mapping: XML schema (`schema/*.orm.xml`) with `strategy="CUSTOM"`

## Database

### MySQL (pdo_mysql) - Pagrindinis
- Naudojamas **tik per Doctrine ORM/DBAL**
- Ateities resemplifikacija (LAMP stack)
- Nenaudojamas tiesiogiai su PDO

### SQLite (pdo_sqlite) - Custom Shared Singletons
- Naudojamas **atskirai nuo ORM/DBAL**
- Custom PDO singleton klasė

#### Shared Singletons

| Component | Class | Description |
|-----------|-------|-------------|
| PDO | `App\Db` | Tiesioginis PDO SQLite singleton |
| EntityManager | `Oryx\ORM\EntityManagerFactory` | Doctrine ORM singleton |
| CacheUnion | `App\Cache\CacheUnion` | Compound cache (L1+L2+L3) |
| PersistentSingletonRegistry | `App\Service\PersistentSingletonRegistry` | DB + in-memory cache |
| Flysystem | `League\Flysystem\Filesystem` | File storage singleton |

## Caching (Compound/Hybrid)

CacheUnion naudoja **3 lygmens** caching strategiją:

| Layer | Implementation | Location | TTL |
|-------|----------------|----------|-----|
| L1 | ArrayCache (in-memory) | `$this->memory` | trumpalaikis |
| L2 | Flysystem (file cache) | `var/cache/` | vidutinis |
| L3 | PersistentSingleton (DB) | SQLite DB | ilgalaikis |

### Reflection on-the-fly

- `XmlThenAttributeDriver` naudoja `ReflectionClass` metadata nuskaitymui
- `League\Container\ReflectionContainer` autowiring'ui
- Doctrine metadata cache naudoja ReflectionClass

## Storage

- **League\Flysystem** - atskiras nuo ORM
- **Adapter:** LocalFilesystemAdapter
- **Path:** `var/storage/`
- **Cache path:** `var/cache/`

## Containerization

Naudojamas **hybrid container** mix:

```
League\Container
├── ReflectionContainer (autowiring)
├── ServiceProviders (Mvc, Event, Flysystem, Tactician)
└── Shared services (addShared())

PHP-DI Container
├── useAutowiring(true)
├── useAttributes(true)
└── ViewRenderer, Forms

Laminas\ServiceManager
└── Form validation
```
