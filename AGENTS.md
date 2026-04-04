# AGENTS.md

## Quick Commands

```bash
# Tests
composer test                              # Run all tests
vendor/bin/phpunit --filter TestName       # Run single test
vendor/bin/phpunit tests/Unit/RoleCollectionTest.php  # Run specific file

# Static Analysis
composer stan                              # PHPStan (src + tests)

# Code Style
composer cs-check                          # Check style (dry-run)
composer cs-fix                            # Auto-fix style issues

# Benchmark
php bin/benchmark.php                      # Full benchmark with memory tracking

# Development
composer serve                             # Start PHP dev server (localhost:8080)
```

## Code Style

### General
- **PHP 8.2+** with `declare(strict_types=1);` on every file
- **PSR-4** autoloading: `App\` → `src/`, `Oryx\ORM\` → `src/Oryx/ORM/`, `Oryx\Cache\` → `src/Oryx/Cache/`
- **No comments** unless explicitly requested
- **No emojis** in code or documentation

### Naming Conventions
| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `User`, `DtoFactory`, `ListUsersAction` |
| Methods | camelCase | `getEmail()`, `toUserDto()` |
| Properties | camelCase | `$userRoles`, `$createdAt` |
| Constants | SCREAMING_SNAKE | `AUTOGENERATE_NEVER` |
| DTO namespace | `App\DTO\` (uppercase) | `App\DTO\UserApiDTO` |
| Factory namespace | `App\Dto\` (PascalCase) | `App\Dto\DtoFactory` |

### Imports
- Group by type: Doctrine, then App, then external libs
- Use FQCN with `use` statements
- Example:
  ```php
  use Doctrine\ORM\Mapping as ORM;
  use Doctrine\Common\Collections\ArrayCollection;
  use App\Entity\User;
  use App\DTO\UserApiDTO;
  use Ramsey\Uuid\UuidInterface;
  ```

### Formatting (PHP-CS-Fixer)
- Rules: `@auto` (non-risky)
- Single quotes preferred
- Trailing commas in multi-line arrays
- 4-space indentation

### Types & Return Types
- All methods must have return types
- Nullable types: `?string`, `?User`
- Union types when needed: `string|int`
- Use `Collection` for Doctrine collections, not `array`

### Error Handling
- Throw `RuntimeException` for unexpected states
- Throw `InvalidArgumentException` for invalid input
- Use `Webmozart\Assert` for preconditions
- Never suppress errors with `@`

## Architecture

### ORM Mapping
- **Primary**: PHP 8 attributes (`#[ORM\Entity]`, `#[ORM\Table]`, etc.)
- **Fallback**: XML schema files in `schema/`
- **Mixed Driver**: `XmlThenAttributeDriver` - XML takes priority, attributes as fallback
- XML schema files are **kept** (not deleted) as reference

### Entities (7 total)
| Entity | Table | Inheritance |
|--------|-------|-------------|
| User | users | - |
| Group | groups | - |
| Role | roles | Base class |
| UserRole | user_roles | Join entity |
| WizardRole | roles | STI (Role) |
| ArchitectRole | roles | STI (Role) |
| GameMasterRole | roles | STI (Role) |

### DTOs & STI
- DTOs replace Fractal transformers in ADR Actions
- `DtoFactory::create()` handles STI resolution via `match` on `instanceof`
- STI order matters: check specific types before base types
  ```php
  match (true) {
      $entity instanceof WizardRole => WizardRoleApiDTO::fromEntity(...),
      $entity instanceof Role => RoleApiDTO::fromEntity(...),
  }
  ```
- Use `get_object_vars()` not `(array)` cast to avoid null-byte prefixed keys

### Containers
- **PHP-DI** (primary): autowiring enabled, `useAutowiring(true)`, `useAttributes(true)`
- **League\Container**: service providers for MVC/Tactician
- **Laminas\ServiceManager**: forms only

### Patterns
- **ADR**: Actions → DTOs → JsonHalResponder (no Fractal)
- **Command/Handler**: Tactician for CQRS
- **Repository**: Custom repositories extend `ObjectRepository`

## Testing

- PHPUnit 9.5 with `tests/bootstrap.php`
- SQLite in-memory by default
- Mock `DtoFactory` in action tests
- Factory Muffin for test data
- Test files: `tests/Unit/`, `tests/Action/`, `tests/Integration/`

## Key Files

| File | Purpose |
|------|---------|
| `src/Kernel.php` | PHP-DI container setup |
| `src/Oryx/ORM/EntityManager.php` | Uses XmlThenAttributeDriver |
| `src/Dto/DtoFactory.php` | STI-aware DTO factory |
| `src/Responder/JsonHalResponder.php` | HAL+JSON responses |
| `bin/benchmark.php` | Performance benchmarks |
| `BENCHMARKS.md` | Benchmark documentation |
