# Requirements

## System Requirements

### PHP
- **PHP 8.2+**

### Required Extensions
| Extension | Purpose |
|-----------|---------|
| `mbstring` | Multibyte string handling |
| `intl` | Internationalization |
| `pdo_sqlite` | Default database driver (bundled) |
| `sodium` | Cryptographically secure UUID generation |
| `json` | JSON encoding/decoding (enabled by default) |

### Optional Extensions
| Extension | Purpose |
|-----------|---------|
| `pdo_mysql` | MySQL/MariaDB database support |
| `memcached` | Memcached caching support |

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

- **Default:** SQLite (zero-config)
- **Supported:** MySQL/MariaDB, PostgreSQL (via Doctrine DBAL drivers)
