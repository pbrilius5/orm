# Oryx ORM Web Skeleton

> DQL-centric ORM for PHP 8.2+ with MVC/ADR dual-pattern, HAL+JSON API, and zero-config SQLite demo.

## Table of Contents

1. [Quick Start (SQLite, 30 seconds)](#1-quick-start-sqlite-30-seconds)
2. [Console CLI Commands](#2-console-cli-commands)
3. [Running the Application](#3-running-the-application)
4. [Architecture Overview](#4-architecture-overview)
5. [MVC Pattern (Vanilla PHP)](#5-mvc-pattern-vanilla-php)
6. [ADR Pattern (laminas/diactoros)](#6-adr-pattern-laminasdiactoros)
7. [HAL+JSON API](#7-haljson-api)
8. [Fractal Transformers](#8-fractal-transformers)
9. [Command/Handler Pattern (Tactician)](#9-commandhandler-pattern-tactician)
10. [Fixtures](#10-fixtures)
11. [Middleware Security](#11-middleware-security)
12. [Logging (Monolog)](#12-logging-monolog)
13. [PWA Support](#13-pwa-support)
14. [Environment Configuration](#14-environment-configuration)
15. [Doctrine Regional Cache](#15-doctrine-regional-cache)
16. [XML Schema-Driven Entity Generation](#16-xml-schema-driven-entity-generation)
17. [Role-Based Access su Doctrine Collections](#17-role-based-access-su-doctrine-collections)
18. [Group STI ir UserGroup](#18-group-sti-ir-usergroup)
19. [Persistent Storage](#19-persistent-storage)
20. [Flysystem File Cache](#20-flysystem-file-cache)
21. [Domain Event Bus](#21-domain-event-bus)
22. [Summary](#22-summary)

---

## 1. Quick Start (SQLite, 30 seconds)

No database server needed. SQLite is the default driver.

```bash
# 1. Install dependencies
composer install

# 2. Copy environment configuration (verbose mode)
cp -v .env.dist .env

# 3. Copy assets from Oryx MVC vendor package
cp -v ./vendor/oryx/mvc/public/favicon.ico ./public/favicon.ico
cp -v ./vendor/oryx/mvc/public/manifest.json ./public/manifest.json
cp -v ./vendor/oryx/mvc/public/sw.js ./public/sw.js
cp -rv ./vendor/oryx/mvc/public/icons/ ./public/icons/

# 4. Create required directories
mkdir -p var/log var/data

# 5. Create database and run migrations
bin/console oryx:db:setup

# 5. Load demo fixtures (groups, roles, users)
bin/console oryx:fixtures:load

# 6. Start the server
composer serve

# 7. (Optional) Start async event worker for background processing
# For development: php bin/async-event-worker.php &
# For production: use supervisor or systemd to manage the worker
```

Open [http://localhost:8080](http://localhost:8080) — you should see the home page with users and API links.

### One Switch Controls Everything

The `APP_ENV` variable in `.env` controls both debug output and proxy generation:

| Mode | Debug Output | Proxy Generation |
|------|--------------|------------------|
| `dev` (default) | Full error details | In-memory (eval) — no files needed |
| `prod` | Generic "An error occurred" | Never auto-generates — run `bin/console orm:proxy:generate` before deploying |

To switch modes, simply edit `.env`:
```bash
# Development (default)
APP_ENV=dev

# Production
APP_ENV=prod
```

No separate debug toggle — one variable, two behaviors.

### What you get

| URL | What |
|-----|------|
| [http://localhost:8080/](http://localhost:8080/) | MVC home page (PHP templates) |
| [http://localhost:8080/users](http://localhost:8080/users) | User list with CRUD |
| [http://localhost:8080/api/users](http://localhost:8080/api/users) | HAL+JSON API collection |
| [http://localhost:8080/api/users/1](http://localhost:8080/api/users/1) | Single user resource |
| [http://localhost:8080/api/users?include=userRoles](http://localhost:8080/api/users?include=userRoles) | With role system |

**Domain Events**: Entities automatically emit `EntityCreated`, `EntityUpdated`, and `EntityDeleted` events on create/update/delete. Use `$emitter->emit($event)` for synchronous handling or `$emitter->emitAsync($event)` for background processing via the async worker.

### Switch to MySQL

Edit `.env.yaml`:

```yaml
database:
  driver: pdo_mysql
  host: localhost
  port: 3306
  name: orm_db
  user: root
  password: secret
```

Then recreate: `bin/console oryx:db:create --force && bin/console oryx:fixtures:load`

### Requirements

- PHP 8.2+
- Extensions: mbstring, intl, pdo_sqlite (included), pdo_mysql (optional)

See [REQUIREMENTS.md](./REQUIREMENTS.md) for complete system requirements and dependencies.

---

## 2. Console CLI Commands

### Available Commands

| Command | Description |
|---------|-------------|
| `bin/console list` | List all commands |
| `bin/console oryx:db:create` | Create SQLite/MySQL database and schema |
| `bin/console oryx:db:setup` | Create database + apply migrations (compound command) |
| `bin/console oryx:fixtures:load` | Load demo fixtures using Faker |
| `bin/console orm:generate:entities` | Generate entity classes from XML schema |

### Database Creation

```bash
# Create SQLite database (default)
bin/console oryx:db:create

# Force recreate (drops existing)
bin/console oryx:db:create --force
```

### Database Setup (Compound Command)

The `oryx:db:setup` command combines database creation and migration application in one step:

```bash
# Create database and apply all migrations
bin/console oryx:db:setup

# Force recreate (drops existing database first)
bin/console oryx:db:setup --force

# Show SQL without executing (dry-run)
bin/console oryx:db:setup --dry-run
```

| Option | Description |
|--------|-------------|
| `--force` | Drop existing database if it exists (SQLite only) |
| `--dry-run` | Show SQL that would be executed without running it |

### Fixtures Loading

```bash
# Default: 3 groups, 10 users
bin/console oryx:fixtures:load

# Custom user count
bin/console oryx:fixtures:load --users=50

# Purge existing data first
bin/console oryx:fixtures:load --purge

# Reproducible random data
bin/console oryx:fixtures:load --seed=42
```

| Option | Default | Description |
|--------|---------|-------------|
| `--users` | 10 | Number of users |
| `--purge` | — | Purge data before loading |
| `--seed` | null | Random seed |

### Entity Generation from XML

```bash
# Generate all entities
bin/console orm:generate:entities

# Generate specific entity
bin/console orm:generate:entities --filter=User
bin/console orm:generate:entities --filter='App\Entity\DeveloperGroup'
```

### Doctrine Migrations

```bash
vendor/bin/doctrine-migrations diff --configuration=migrations.yaml
vendor/bin/doctrine-migrations migrate --configuration=migrations.yaml
vendor/bin/doctrine-migrations status --configuration=migrations.yaml
```

---

## 3. Running the Application

### Development Server

```bash
php -S localhost:8080 -t public
# or
composer serve
```

### Access Points

| URL | Pattern | Entry |
|-----|---------|-------|
| [http://localhost:8080/](http://localhost:8080/) | MVC | Vanilla HTML |
| [http://localhost:8080/users](http://localhost:8080/users) | MVC | Vanilla HTML |
| [http://localhost:8080/api/users](http://localhost:8080/api/users) | ADR | HAL+JSON |
| [http://localhost:8080/api/users/1](http://localhost:8080/api/users/1) | ADR | HAL+JSON |
| [http://localhost:8080/manifest.json](http://localhost:8080/manifest.json) | PWA | JSON Manifest |

### Testing

```bash
composer test
vendor/bin/phpunit --testsuite Action
vendor/bin/phpunit --coverage-text
```

### API Testing

For complete API testing documentation including all endpoint references, curl examples, Postman collection, and validation specimens, see [TESTER.md](./TESTER.md).

---

## 4. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                      ORYX WEB SKELETON                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    Entry Points                           │    │
│  │  public/index.php → Routes MVC ↔ ADR                     │    │
│  │  public/mvc.php   → MVC only                             │    │
│  │  public/api.php   → ADR only                             │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│          ┌──────────────────┴──────────────────┐              │
│          ▼                                      ▼              │
│  ┌─────────────────────┐        ┌─────────────────────────┐   │
│  │    MVC Layer        │        │      ADR Layer          │   │
│  │  (Vanilla PHP)     │        │  (laminas/diactoros)    │   │
│  ├─────────────────────┤        ├─────────────────────────┤   │
│  │ • App\Http\Request │        │ • App\Kernel            │   │
│  │ • App\Http\Response│        │ • App\Action\User\*    │   │
│  │ • App\Http\Router │        │ • League\Fractal        │   │
│  │ • PHP Templates   │        │ • JsonHalResponder      │   │
│  └─────────────────────┘        └─────────────────────────┘   │
│                              │                                   │
│  ┌────────────────────────────┴───────────────────────────┐    │
│  │              Command/Handler Pattern                   │    │
│  │  • League\Tactician (CommandBus)                      │    │
│  │  • 16 Commands + 16 Handlers (User/Group/Console)    │    │
│  │  • PHP-DI autowiring for dependency injection          │    │
│  └───────────────────────────────────────────────────────┘    │
│                              │                                   │
│  ┌────────────────────────────┴───────────────────────────┐    │
│  │                   Logging (Monolog)                     │    │
│  │  • AppLogger (dev: Debug, prod: Error)                 │    │
│  │  • CrashLogger (critical errors → crash.log)           │    │
│  └───────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. MVC Pattern (Vanilla PHP)

**MVC uses NO external HTTP libraries** - pure PHP for maximum compatibility.

### 5.1 HTTP Layer (Vanilla)

```php
// src/Http/Request.php
namespace App\Http;

class Request
{
    public function getMethod(): string { /* $_SERVER['REQUEST_METHOD'] */ }
    public function getPath(): string { /* parse_url() */ }
    public function get(string $key, $default = null) { /* $_GET */ }
    public function post(string $key, $default = null) { /* $_POST */ }
}
```

```php
// src/Http/Response.php
namespace App\Http;

class Response
{
    public function __construct(string $content, int $status = 200, array $headers = []);
    public function send(): void { /* header() + echo */ }
}
```

```php
// src/Http/Router.php
namespace App\Http;

class Router
{
    public function get(string $path, callable $handler): void;
    public function post(string $path, callable $handler): void;
    public function dispatch(Request $request): ?Response;
}
```

### 5.2 MVC Controller

```php
// src/Controller/UserController.php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserController
{
    private EntityManagerInterface $em;
    private UserRepository $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = new UserRepository($em);
    }

    public function index(): array
    {
        $users = $this->repository->findAll();
        return ['users' => $users];
    }

    public function show(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function create(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'] ?? '', PASSWORD_BCRYPT));
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function delete(int $id): bool
    {
        $user = $this->repository->find($id);
        if (!$user) {
            return false;
        }
        $this->em->remove($user);
        $this->em->flush();
        return true;
    }
}
```

### 5.3 MVC Application

```php
// src/App/MvcApplication.php
namespace App;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\View\ViewRenderer;
use Doctrine\ORM\EntityManager;

class MvcApplication
{
    public function __construct(EntityManager $em)
    {
        $this->router = new Router();
        $this->view = new ViewRenderer();
    }

    public function run(): void
    {
        $request = new Request();
        $response = $this->router->dispatch($request);
        $response->send();
    }
}
```

### 5.4 MVC Routes

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/` | Home page |
| `GET` | `/users` | User list |
| `GET` | `/users/create` | Create form |
| `POST` | `/users/create` | Create user |
| `GET` | `/users/{id}` | Show user |

---

## 6. ADR Pattern (laminas/diactoros)

**ADR uses PSR-7/PSR-15 for modern HTTP handling.**

### 6.1 Kernel + Routing atskirumas

Maršrutai atskirti nuo Kernelio į `App\Routing\*Routes` klases - lengviau tvarkyti ir testuoti.

```php
// src/App/Kernel.php
namespace App;

class Kernel
{
    private AdrRoutes $adrRoutes;

    public function __construct(string $environment = 'dev')
    {
        $this->adrRoutes = new AdrRoutes();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->adrRoutes->getRouter()->dispatch($request);
    }
}
```

```php
// src/Routing/AdrRoutes.php
namespace App\Routing;

use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Laminas\Diactoros\ResponseFactory;

class AdrRoutes
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->router->setStrategy(new JsonStrategy(new ResponseFactory()));
        $this->register();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    private function register(): void
    {
        $this->router->map('GET', '/api/health', fn() => new JsonResponse(['status' => 'ok']));
        $this->router->map('GET', '/api/users', [ListAction::class, '__invoke']);
        // ... kiti maršrutai
    }
}
```

**MVC Routes atskirai:**
```php
// src/Routing/MvcRoutes.php
namespace App\Routing;

use App\Http\Router;
use App\Http\Request;
use App\Http\Response;
use App\View\ViewRenderer;
use App\Controller\UserController;
use Doctrine\ORM\EntityManager;

class MvcRoutes
{
    private Router $router;
    private array $controllers;
    private ViewRenderer $view;

    public function __construct(EntityManager $em)
    {
        $this->router = new Router();
        $this->view = new ViewRenderer();
        $this->controllers = [
            'user' => new UserController($em),
        ];
        $this->register();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    private function register(): void
    {
        $this->router->get('/', function (Request $req) {
            return new Response($this->view->render('home'));
        });

        $this->router->get('/users', function (Request $req) {
            $data = $this->controllers['user']->index();
            return new Response($this->view->render('users/index', $data));
        });

        $this->router->get('/users/{id}', function (Request $req, array $params) {
            $user = $this->controllers['user']->show((int) $params['id']);
            return new Response($this->view->render('users/show', ['user' => $user]));
        });
    }
}
```

**Abiejų routing'ų sujungimas Kernel'yje:**
```php
// src/App/Kernel.php
class Kernel
{
    private AdrRoutes $adrRoutes;
    private MvcRoutes $mvcRoutes;

    public function __construct(EntityManager $em)
    {
        $this->adrRoutes = new AdrRoutes();
        $this->mvcRoutes = new MvcRoutes($em);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // ADR maršrutai (API)
        if (str_starts_with($request->getUri()->getPath(), '/api/')) {
            return $this->adrRoutes->getRouter()->dispatch($request);
        }
        // MVC maršrutai (HTML)
        return $this->mvcRoutes->getRouter()->dispatch($request);
    }
}
```

### 6.2 ADR Actions (Invokable)

```php
// src/Action/User/ListAction.php
namespace App\Action\User;

use App\Fixture\FixtureLoader;
use App\Entity\User;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListAction
{
    private FixtureLoader $loader;
    private Manager $fractal;

    public function __construct(FixtureLoader $loader)
    {
        $this->loader = $loader;
        $this->fractal = new Manager();
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $users = $this->loader->makeMany(User::class, 5);
        $resource = new Collection($users, new \App\Transformer\Resource\UserTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return new JsonResponse($data, 200, [
            'Content-Type' => 'application/hal+json',
        ]);
    }
}
```

### 6.3 JSON:HAL Responder

```php
// src/Responder/JsonHalResponder.php
namespace App\Responder;

use Laminas\Diactoros\Response\JsonResponse;

class JsonHalResponder
{
    public static function resource(string $type, string $id, array $attributes, array $links = []): JsonResponse
    {
        $data = [
            '_links' => [
                'self' => ['href' => "/{$type}/{$id}"],
            ],
            '_embedded' => [],
        ];

        foreach ($links as $rel => $href) {
            $data['_links'][$rel] = ['href' => $href];
        }

        $data[$type] = array_merge(['id' => $id], $attributes);

        return new JsonResponse($data);
    }

    public static function collection(string $type, array $items, array $meta = []): JsonResponse
    {
        $data = [
            '_links' => [
                'self' => ['href' => "/{$type}"],
            ],
            '_embedded' => [
                $type => $items,
            ],
            '_meta' => $meta,
        ];

        return new JsonResponse($data);
    }

    public static function error(string $title, int $status, string $detail = ''): JsonResponse
    {
        return new JsonResponse([
            '_error' => [
                'status' => $status,
                'title' => $title,
                'detail' => $detail,
            ],
        ], $status);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }
}
```

---

## 7. HAL+JSON API

**Full CRUD operations with HAL+JSON responses.**

### 7.1 API Endpoints

| Method | Endpoint | Action | Response | Description |
|--------|----------|--------|----------|-------------|
| `GET` | `/api/users` | ListAction | `200 + HAL collection` | List all users |
| `GET` | `/api/users/{id}` | ShowAction | `200 + HAL resource` | Get single user |
| `POST` | `/api/users` | CreateAction | `201 + HAL resource` | Create user |
| `PUT` | `/api/users/{id}` | UpdateAction | `200 + HAL resource` | Full update |
| `PATCH` | `/api/users/{id}` | PatchAction | `200 + HAL resource` | Partial update |
| `DELETE` | `/api/users/{id}` | DeleteAction | `204 No Content` | Delete user |
| `GET` | `/api/groups` | ListAction | `200 + HAL collection` | List all groups |
| `GET` | `/api/groups/{id}` | ShowAction | `200 + HAL resource` | Get single group |
| `POST` | `/api/groups` | CreateAction | `201 + HAL resource` | Create group |
| `PUT` | `/api/groups/{id}` | UpdateAction | `200 + HAL resource` | Full update |
| `PATCH` | `/api/groups/{id}` | PatchAction | `200 + HAL resource` | Partial update |
| `DELETE` | `/api/groups/{id}` | DeleteAction | `204 No Content` | Delete group |

> **Full API Testing Guide:** See [TESTER.md](./TESTER.md) for complete request/response specimens, Postman collection, and validation examples.

### 7.2 HAL+JSON: Codinga API atsakus

**Kas yra HAL?** Hypertext Application Language - standartas, kuris suteikia nuorodas (`_links`) ir įdėtinius resursus (`_embedded`). Skirtumas nuo JSON:API: paprastesnis, lengviau suprasti.

**Kodėl Fractal?** Transformuoja Doctrine entitetus į masyvus, prideda nuorodas ir įdėtinius resursus.

### 7.3 Pagrindinės HAL struktūros

**1. Vienas resursas (`/api/users/1`):**
```json
{
  "_links": {
    "self": { "href": "/api/users/1" }
  },
  "_embedded": {
    "user": {
      "id": 1,
      "email": "admin@versliukai.lt",
      "roles": ["ROLE_ADMIN"],
      "created_at": "2024-01-15T10:30:00+02:00",
      "updated_at": "2024-01-20T15:45:00+02:00"
    }
  }
}
```

**2. Kolekcija (`/api/users`):**
```json
{
  "_links": {
    "self": { "href": "/api/users" },
    "next": { "href": "/api/users?page=2" }
  },
  "_embedded": {
    "users": [
      { "id": 1, "email": "admin@versliukai.lt", "roles": ["ROLE_ADMIN"] },
      { "id": 2, "email": "user@versliukai.lt", "roles": ["ROLE_USER"] }
    ]
  },
  "_meta": {
    "total": 150,
    "count": 2,
    "page": 1,
    "per_page": 2
  }
}
```

**3. Klaida (`400/404/422/500`):**
```json
{
  "_error": {
    "status": 422,
    "title": "Unprocessable Entity",
    "detail": "Validation failed",
    "errors": {
      "email": "Invalid email format",
      "password": "Must be at least 8 characters"
    }
  }
}
```

### 7.4 Nuorodos tarp resursų (`_links`)

Nuorodos leidžia klientams naviguoti API be hardkodotų URL'ų:

```json
{
  "_links": {
    "self": { "href": "/api/users/1" },
    "collection": { "href": "/api/users" },
    "posts": { "href": "/api/users/1/posts" },
    "group": { "href": "/api/groups/1" },
    "edit": { "href": "/api/users/1", "method": "PUT" },
    "delete": { "href": "/api/users/1", "method": "DELETE" }
  },
  "_embedded": {
    "user": { ... }
  }
}
```

**Praktinis pavyzdys - PWA navigacija:**
```javascript
const response = await fetch('/api/users/1');
const data = await response.json();

const editUrl = data._links.edit.href;
const postsUrl = data._links.posts.href;
```

### 7.5 Įdėtiniai resursai (`_embedded`)

Įdėtiniai resursai neleidžia N+1 užklausų problemų - viena užklausa gauna viską.

**Su autoriumi (`/api/posts/1?include=author`):**
```json
{
  "_links": { "self": { "href": "/api/posts/1" } },
  "_embedded": {
    "post": { "id": 1, "title": "Kaip sukurti REST API", "content": "..." },
    "author": { "id": 1, "email": "admin@versliukai.lt", "roles": ["ROLE_ADMIN"] }
  }
}
```

**Su visais ryšiais (`/api/users/1?include=posts,group`):**
```json
{
  "_links": { "self": { "href": "/api/users/1" } },
  "_embedded": {
    "user": { "id": 1, "email": "admin@versliukai.lt" },
    "posts": [
      { "id": 1, "title": "Kaip sukurti REST API" },
      { "id": 2, "title": "Middleware saugumas" }
    ],
    "group": { "id": 1, "name": "Administratoriai" }
  }
}
```

### 7.6 Maršrutų struktūra

```
src/
├── App/Kernel.php              # tik inicializacija + middleware
├── Routing/
│   ├── AdrRoutes.php          # API maršrutai
│   └── MvcRoutes.php          # MVC maršrutai
├── Action/User/                # ADR veiksmai
│   ├── ListAction.php
│   └── ...
└── Controller/                 # MVC kontroleriai
    └── UserController.php
```

---

## 8. Fractal Transformers

**Transformers convert entities to HAL format.**

### 8.1 User Transformer

```php
// src/Transformer/Resource/UserTransformer.php
namespace App\Transformer\Resource;

use App\DTO\UserApiDTO;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(UserApiDTO $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->createdAt->format('c'),
        ];
    }
}
```

### 8.2 Group Transformer

```php
// src/Transformer/Resource/GroupTransformer.php
namespace App\Transformer\Resource;

use App\DTO\GroupApiDTO;
use League\Fractal\TransformerAbstract;

class GroupTransformer extends TransformerAbstract
{
    public function transform(GroupApiDTO $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'created_at' => $group->createdAt->format('c'),
        ];
    }
}
```

### 8.2 Using Transformers in Actions

```php
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\JsonApiSerializer;

$fractal = new Manager();
$fractal->setSerializer(new JsonApiSerializer());

$users = $this->repository->findAll();
$resource = new Collection($users, new UserTransformer());
$data = $fractal->createData($resource)->toArray();
```

---

## 9. Command/Handler Pattern (Tactician)

**Unified Command/Handler pattern for MVC, ADR, and Console using League\Tactician.**

### 9.1 Why Command/Handler?

Separates request handling from business logic:
- **Commands** - Simple objects representing intent (e.g., `CreateUserCommand`)
- **Handlers** - Execute business logic (e.g., `CreateUserHandler`)
- **CommandBus** - Dispatches commands to appropriate handlers

This enables:
- Single responsibility principle
- Easy testing (mock handlers)
- Consistent handling across MVC, ADR, and Console

### 9.2 Architecture

```
src/
├── Command/                    # Command objects
│   ├── User/
│   │   ├── ListUsersCommand.php
│   │   ├── GetUserCommand.php
│   │   ├── CreateUserCommand.php
│   │   ├── UpdateUserCommand.php
│   │   ├── PatchUserCommand.php
│   │   └── DeleteUserCommand.php
│   ├── Group/
│   │   ├── ListGroupsCommand.php
│   │   ├── GetGroupCommand.php
│   │   ├── CreateGroupCommand.php
│   │   ├── UpdateGroupCommand.php
│   │   ├── PatchGroupCommand.php
│   │   └── DeleteGroupCommand.php
│   ├── Console/
│   │   ├── ManageUserCommand.php
│   │   ├── CreateDatabaseCommand.php
│   │   ├── LoadFixturesCommand.php
│   │   └── GenerateProxiesCommand.php
│   └── CommandBusInterface.php  # Interface for testing
├── Handler/                    # Handler objects
│   ├── User/                   # 6 handlers
│   ├── Group/                  # 6 handlers
│   └── Console/                # 4 handlers
└── Container/
    └── TacticianServiceProvider.php  # DI setup
```

### 9.3 CommandBusInterface

Created to enable mocking in tests (since `League\Tactician\CommandBus` is `final`):

```php
// src/Command/CommandBusInterface.php
namespace App\Command;

interface CommandBusInterface
{
    /**
     * @param object $command
     * @return mixed
     */
    public function handle($command);
}
```

### 9.4 Using Commands in Actions

```php
// src/Action/User/ListAction.php
namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\ListUsersCommand;
use League\Fractal\Manager;

class ListAction
{
    private CommandBusInterface $commandBus;
    private Manager $fractal;

    public function __construct(CommandBusInterface $commandBus, Manager $fractal)
    {
        $this->commandBus = $commandBus;
        $this->fractal = $fractal;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $command = new ListUsersCommand(
            limit: (int) ($request->getQueryParams()['limit'] ?? 10),
            offset: (int) ($request->getQueryParams()['offset'] ?? 0),
            search: $request->getQueryParams()['search'] ?? null
        );

        $users = $this->commandBus->handle($command);
        // ... transform and return response
    }
}
```

### 9.5 Console Commands

```bash
# List all users
bin/console user:list

# Create user
bin/console user:create --email=user@example.com --password=secret

# Update user
bin/console user:update 1 --email=new@example.com

# Delete user
bin/console user:delete 1

# Manage user (interactive)
bin/console user:manage
```

### 9.6 Testing with CommandBusInterface

```php
// tests/Action/UserActionTest.php
use App\Command\CommandBusInterface;

protected function setUp(): void
{
    $this->commandBus = new class implements CommandBusInterface {
        public function handle($command) {
            return []; // Return mock data
        }
    };
}
```

---

## 10. Fixtures

**Fixtures provide test data generation.**

### 10.1 FixturesLoadCommand

```php
// src/Console/Command/FixturesLoadCommand.php
// Creates: 3 groups (Developer, Designer, Tester)
//          3 roles (Wizard, Architect, GameMaster)
//          N users with random gamification roles
```

### 10.2 Gamification Scale

| Group | Role |
|-------|------|
| TesterGroup | WizardRole |
| DesignerGroup | ArchitectRole |
| DeveloperGroup | GameMasterRole |

Each user gets **one random role** from the gamification scale.

### 9.3 Using Fixtures in Tests

```php
// tests/Action/UserActionTest.php
public function testListActionReturnsHalJson(): void
{
    $loader = new FixtureLoader();
    $action = new ListAction($loader);

    $result = $action($request, $response);

    $this->assertEquals(200, $result->getStatusCode());
    $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
}
```

---

## 10. Middleware Security

**Middleware provides security, CORS, rate limiting.**

### 10.1 Security Middleware

```php
// src/Middleware/SecurityMiddleware.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class SecurityMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            ->withHeader('Content-Security-Policy', "default-src 'self'");
    }
}
```

### 10.2 CORS Middleware

```php
// src/Middleware/CorsMiddleware.php
namespace App\Middleware;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, 204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        $response = $handler->handle($request);

        return $response->withHeader('Access-Control-Allow-Origin', '*');
    }
}
```

### 10.3 Rate Limiting Middleware

```php
// src/Middleware/RateLimitMiddleware.php
namespace App\Middleware;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $key = 'rate_limit:' . ($request->getHeaderLine('X-Forwarded-For') ?: 'local');

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) ($this->maxRequests - 1));
    }
}
```

### 10.4 Applying Middleware to Kernel

```php
$this->router->middleware(new SecurityMiddleware());
$this->router->middleware(new CorsMiddleware());
$this->router->middleware(new RateLimitMiddleware(100, 60));
```

---

## 12. Logging (Monolog)

**Monolog-based logging with environment-aware log levels and crash handling.**

### 12.1 Log Levels by Environment

| Environment | Log Level | Log File |
|-------------|-----------|----------|
| `dev` | `Debug` | `var/log/app_dev.log` |
| `prod` | `Error` | `var/log/app_prod.log` |

### 12.2 LoggerFactory

```php
// src/Logger/LoggerFactory.php
namespace App\Logger;

class LoggerFactory
{
    public static function create(string $appEnv = 'dev'): LoggerInterface
    {
        $level = match ($appEnv) {
            'dev' => Level::Debug,
            'prod' => Level::Error,
            default => Level::Debug,
        };

        $logFile = 'var/log/app_' . $appEnv . '.log';

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logFile, $level));
        $logger->pushProcessor(new UidProcessor());

        return $logger;
    }
}
```

### 12.3 AppLogger Usage

```php
use App\Logger\AppLogger;
use Psr\Log\LogLevel;

class UserController
{
    public function __construct(private AppLogger $logger) {}

    public function index(): array
    {
        $this->logger->info('User list accessed');
        // ...
    }
}
```

### 12.4 CrashLogger (Critical Errors)

**Crash logger** captures critical errors to `var/log/crash.log`:

```php
// src/Logger/CrashLogger.php
class CrashLogger
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::createCrashLogger();
    }

    public function log(\Throwable $e): void
    {
        $this->logger->critical('Critical error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
```

**Crashes captured:**
- `Error` (all PHP errors)
- `OutOfMemoryError`
- `Segmentation fault` (via custom handler)
- `Killed` (SIGKILL signal)

### 12.5 Kernel Crash Handling

```php
// src/Kernel.php
set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity === E_ERROR) {
        $crashLogger = LoggerFactory::createCrashLogger();
        $crashLogger->critical("PHP Error: $message in $file:$line");
    }
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $crashLogger = LoggerFactory::createCrashLogger();
        $crashLogger->critical("Fatal error: {$error['message']} in {$error['file']}:{$error['line']}");
    }
});
```

---

## 13. PWA Support

**Progressive Web App capabilities with offline-first caching.**

### 11.1 Manifest Configuration

The PWA manifest defines how your app appears when installed:

| Property | Value |
|----------|-------|
| Name | Oryx ORM App |
| Short Name | OryxApp |
| Display | standalone |
| Start URL | / |
| Theme Color | #4A90E2 |
| Background Color | #ffffff |

```json
{
  "name": "Oryx ORM App",
  "short_name": "OryxApp",
  "description": "Full-stack ORM with ADR pattern",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#4A90E2",
  "icons": [
    {
      "src": "/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

### 11.2 Service Worker

Located at `public/sw.js` with cache-first strategy:
- Pre-caches: `/`, `/index.php`, `/manifest.json`, `/favicon.ico`, and icons
- Serves cached assets when offline
- Automatically updates on new versions

### 11.3 PWA Installation

After copying assets (Step 3 in Quick Start):
1. Visit `http://localhost:8080/`
2. Browser will show "Install" prompt or use menu → "Add to Home Screen"
3. Works offline after first visit

### 11.4 Manual PWA File Copy

If you need to manually copy PWA assets:
```bash
# Manifest and Service Worker
cp -v ./vendor/oryx/mvc/public/manifest.json ./public/manifest.json
cp -v ./vendor/oryx/mvc/public/sw.js ./public/sw.js

# Icons (recursive copy)
cp -rv ./vendor/oryx/mvc/public/icons/ ./public/icons/
```

---

## 14. Environment Configuration

### 14.1 Configuration Files

```
.env.dist          # Template defaults (committed to VCS) - all variables documented here
.env.yaml          # Primary YAML config (RECOMMENDED)
.env               # Legacy override (optional, gitignored)
.env.yaml.local    # Local overrides (optional, gitignored)
```

### 14.2 Setup Instructions

**Step 1:** Copy the template:
```bash
cp .env.dist .env          # Legacy KEY=VALUE format (optional)
cp .env.yaml .env.yaml.local  # Local overrides (recommended)
```

**Step 2:** Edit `.env.yaml.local` (recommended) or `.env.yaml` directly:
```yaml
database:
  driver: pdo_mysql
  host: localhost
  port: 3306
  name: my_database
  user: my_user
  password: my_secret
```

> **NOTE:** YAML configuration (`.env.yaml`) is the primary and recommended format.
> The legacy `.env` file (KEY=VALUE) is supported for backward compatibility only.
> All available variables are documented in `.env.dist`.

### 14.3 Loading Priority

1. **System environment variables** - `$_ENV`, `$_SERVER`
2. **`.env` file** - Legacy KEY=VALUE format (if present)
3. **`.env.yaml`** - Primary YAML configuration
4. **`.env.dist`** - Template defaults

### 14.4 YAML Configuration Format

```yaml
database:
  host: ${DB_HOST:-localhost}
  port: ${DB_PORT:-3306}
  name: ${DB_NAME:-orm_db}
  user: ${DB_USER:-root}
  password: ${DB_PASSWORD:-}
  charset: ${DB_CHARSET:-utf8mb4}
  driver: ${DB_DRIVER:-pdo_sqlite}
  path: ${DB_PATH:-var/data/orm.db}

app:
  env: ${APP_ENV:-dev}
  secret: ${APP_SECRET:-change-me-in-production}

orm:
  proxy_dir: ${ORM_PROXY_DIR:-/tmp/orm/proxies}
  proxy_namespace: ${ORM_PROXY_NAMESPACE:-Oryx\\ORM\\Proxy}
```

### 14.5 Variable Substitution Syntax

| Syntax | Description | Example |
|--------|-------------|---------|
| `${VAR}` | Direct reference | `${DB_HOST}` |
| `${VAR:-default}` | Default if not set | `${DB_HOST:-localhost}` |
| `${VAR:?error}` | Error if not set | `${DB_PASSWORD:?Required}` |
| `${nested.key}` | Nested reference | `${database.host}` |

### 14.6 Using EnvironmentConfig in Code

```php
use App\EnvironmentConfig;

$config = new EnvironmentConfig();

$host = $config->get('DB_HOST', 'localhost');
$params = $config->getDatabaseParams();

if ($config->isDebug()) {
    // Development mode
}

$secret = $config->require('APP_SECRET', 'Application secret is required');
```

### 14.7 Environment Variables Reference

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `DB_HOST` | Database hostname | `localhost` | No |
| `DB_PORT` | Database port | `3306` | No |
| `DB_NAME` | Database name | `orm_db` | No |
| `DB_USER` | Database username | `root` | No |
| `DB_PASSWORD` | Database password | (empty) | No |
| `DB_CHARSET` | Database charset | `utf8mb4` | No |
| `DB_DRIVER` | Database driver | `pdo_sqlite` | No |
| `DB_PATH` | SQLite file path | `var/data/orm.db` | No |
| `APP_ENV` | Application environment (dev/prod) | `dev` | No |
| `APP_SECRET` | Application secret key | `change-me-in-production` | No |
| `ORM_PROXY_DIR` | Proxy directory storage | `/tmp/orm/proxies` | No |
| `ORM_PROXY_NAMESPACE` | Proxy namespace | `Oryx\ORM\Proxy` | No |
| `CACHE_DRIVER` | Cache driver | `array` | No |
| `CACHE_TTL` | Cache time-to-live (seconds) | `3600` | No |
| `RATE_LIMIT_ENABLED` | Enable rate limiting | `true` | No |
| `RATE_LIMIT_MAX_REQUESTS` | Max requests per window | `60` | No |
| `RATE_LIMIT_WINDOW` | Rate limit window (seconds) | `60` | No |
| `MAILER_TRANSPORT` | Mailer transport | `smtp` | No |
| `MAILER_HOST` | Mailer hostname | `localhost` | No |
| `MAILER_PORT` | Mailer port | `25` | No |
| `MAILER_USER` | Mailer username | (empty) | No |
| `MAILER_PASSWORD` | Mailer password | (empty) | No |

---

## 15. XML Schema-Driven Entity Generation

### 16.1 Schema Location

All Doctrine XML mappings live in `/schema`:

```
schema/
├── User.orm.xml
├── Group.orm.xml
└── Role.orm.xml
```

### 16.2 Pipeline

```
schema/*.orm.xml → bin/console orm:generate:entities → src/Entity/*.php
```

### 16.3 Example Schema

```xml
<!-- schema/User.orm.xml -->
<doctrine-mapping>
    <entity name="App\Entity\User" table="users">
        <id name="id" type="integer">
            <generator strategy="AUTO"/>
        </id>
        <field name="email" type="string" length="255" unique="true"/>
        <field name="password" type="string" length="255"/>
        <field name="createdAt" type="datetime"/>
        <field name="updatedAt" type="datetime" nullable="true"/>
        
        <many-to-one target-entity="App\Entity\Group" field="group" inversed-by="users">
            <join-column name="group_id" nullable="true"/>
        </many-to-one>
        
        <one-to-many target-entity="App\Entity\UserRole" field="userRoles" mapped-by="user" cascade="persist" orphan-removal="true"/>
    </entity>
</doctrine-mapping>
```

---

## 17. Role-Based Access su Doctrine Collections

**Gamified role system su STI (Single Table Inheritance) ir grupių priskyrimu.**

### 17.1 Schema Overview

| Entity | Table | Description |
|--------|-------|-------------|
| `Role` | `roles` | Base class (hidden from API) |
| `WizardRole` | `roles` | STI (discriminator: `wizard`) |
| `ArchitectRole` | `roles` | STI (discriminator: `architect`) |
| `GameMasterRole` | `roles` | STI (discriminator: `game_master`) |
| `UserRole` | `user_roles` | Join entity: user_id + role_id |
| `Group` | `groups` | Base class (hidden from API) |
| `DeveloperGroup` | `groups` | STI (discriminator: `developer`) |
| `DesignerGroup` | `groups` | STI (discriminator: `designer`) |
| `TesterGroup` | `groups` | STI (discriminator: `tester`) |
| `UserGroup` | `user_groups` | Join entity: user_id + group_id |

### 17.2 STI (Single Table Inheritance)

Roles ir Groups naudoja STI - viena lentelė su discriminator stulpeliu:

```php
// Role STI hierarchija
Role (bazinė - paslėpta nuo API)
├── WizardRole
├── ArchitectRole
└── GameMasterRole

// Group STI hierarchija
Group (bazinė - paslėpta nuo API)
├── DeveloperGroup
├── DesignerGroup
└── TesterGroup
```

**Bazinės klasės yra paslėptos nuo API** - DtoFactory meta `RuntimeException`, Repository filtruoja pagal `discr` stulpelį.

### 17.3 Role-Group Mapping (Gamification Scale)

Kiekviena grupė turi susietą gamifikacinę rolę:

| Group | Role |
|-------|------|
| TesterGroup | WizardRole |
| DesignerGroup | ArchitectRole |
| DeveloperGroup | GameMasterRole |

### 17.4 DTO Factory

`DtoFactory::create()` automatiškai atpažįsta STI tipą per `instanceof`:

```php
match (true) {
    $entity instanceof WizardRole => WizardRoleApiDTO::fromEntity(...),
    $entity instanceof ArchitectRole => ArchitectRoleApiDTO::fromEntity(...),
    $entity instanceof GameMasterRole => GameMasterRoleApiDTO::fromEntity(...),
    $entity instanceof Role => throw new RuntimeException('Base Role hidden'),
    $entity instanceof DeveloperGroup => DeveloperGroupApiDTO::fromEntity(...),
    $entity instanceof DesignerGroup => DesignerGroupApiDTO::fromEntity(...),
    $entity instanceof TesterGroup => TesterGroupApiDTO::fromEntity(...),
    $entity instanceof Group => throw new RuntimeException('Base Group hidden'),
}
```

### 17.5 API Response (supaprastintas)

**User:**
```json
{
  "id": "uuid",
  "email": "user@example.com",
  "role": "ROLE_WIZARD",
  "created_at": "2026-04-07T10:00:00+00:00"
}
```

**Group:**
```json
{
  "id": "uuid",
  "name": "Developers",
  "created_at": "2026-04-07T10:00:00+00:00"
}
```

### 17.5 API Pavyzdžiai

**GET /api/users/1**

```json
{
  "_links": {
    "self": { "href": "/api/users/1" },
    "collection": { "href": "/api/users" }
  },
  "data": {
    "id": "uuid",
    "email": "user@example.com",
    "role": "ROLE_WIZARD",
    "created_at": "2026-04-07T10:00:00+00:00"
  }
}
```
| **User** | email, password, createdAt, userRoles, wands, patronuses, cloaks | 12 tests |

---

## 18. Group STI ir UserGroup

**Group entity naudoja Single Table Inheritance (STI) kaip ir Role.**

### 18.1 Group STI Hierarchija

| Entity | Table | Discriminator | Description |
|--------|-------|---------------|-------------|
| `DeveloperGroup` | `groups` | `developer` | Developerių grupė |
| `DesignerGroup` | `groups` | `designer` | Designerių grupė |
| `TesterGroup` | `groups` | `tester` | Testerių grupė |

Bazinė `Group` klasė yra **paslėpta nuo API** - DtoFactory meta `RuntimeException`, Repository filtruoja pagal `discr <> 'group'`.

### 18.2 UserGroup (Many-to-Many)

Vartotojai gali priklausyti kelioms grupėms per `UserGroup` join entity:

```php
// User -> Group (per UserGroup)
$user->addGroup($group);
$user->removeGroup($group);
$user->hasGroup('Developers');
$user->getGroups();       // [DeveloperGroup, DesignerGroup]
```

### 18.3 Privaloma Grupė

`UserForm` reikalauja `group_id` lauko:

```php
// UserForm.php
$groupSpec = [
    'name' => 'group_id',
    'required' => true,
];
```

### 18.4 Schema Files

```
schema/
├── Group.orm.xml       # STI mapping
├── UserGroup.orm.xml   # Many-to-many join
└── User.orm.xml        # UserGroup OneToMany
```

---

## 19. Persistent Storage

**DB-backed singleton registry with in-memory cache layer.**

### 19.1 PersistentSingletonRegistry

```php
use App\Service\PersistentSingletonRegistry;

$registry = $container->get(PersistentSingletonRegistry::class);

// Store a value (serialized to DB)
$registry->set('my_service_config', ['timeout' => 30, 'retries' => 3]);

// Retrieve (cached in memory after first DB hit)
$config = $registry->get('my_service_config');

// Check existence
if ($registry->has('my_service_config')) {
    // ...
}

// Remove
$registry->remove('my_service_config');
```

### 19.2 How It Works

```
┌─────────────────────────────────────────────────┐
│            PersistentSingletonRegistry           │
├─────────────────────────────────────────────────┤
│                                                  │
│  1. Check in-memory $cache array                 │
│     ↓ (miss)                                     │
│  2. Query persistent_singleton table by key      │
│     ↓ (found)                                    │
│  3. Unserialize value, store in cache            │
│     ↓                                            │
│  4. Return value                                 │
│                                                  │
│  On set():                                       │
│  1. Serialize value                              │
│  2. Upsert to persistent_singleton table         │
│  3. Update in-memory cache                       │
│                                                  │
└─────────────────────────────────────────────────┘
```

### 19.3 Use Cases

| Scenario | Key Pattern | Value |
|----------|-------------|-------|
| Service config | `config.service_name` | Array of settings |
| Feature flags | `flag.feature_name` | Boolean |
| Rate limit state | `rate_limit.ip_hash` | Counter array |
| Last processed ID | `cursor.entity_type` | Integer |

### 19.4 Schema

```sql
CREATE TABLE persistent_singleton (
    id CHAR(36) PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

---

## 20. Flysystem File Cache

**Hybrid entity-backed file storage: metadata in DB, files on disk.**

### 20.1 Quick Start

```php
use App\Service\FlysystemService;

$fileService = $container->get(FlysystemService::class);

// Store file with metadata
$fileCache = $fileService->store(
    key: 'user_avatar_123',
    content: $imageData,
    expiresAt: new \DateTimeImmutable('+30 days')
);

// Retrieve
$content = $fileService->retrieve('user_avatar_123');

// Check existence
if ($fileService->exists('user_avatar_123')) {
    // ...
}

// Invalidate (deletes file + DB record)
$fileService->invalidate('user_avatar_123');

// Get metadata only
$metadata = $fileService->getMetadata('user_avatar_123');
echo $metadata->getSize(); // bytes
echo $metadata->getHash(); // sha256
```

### 20.2 Architecture

```
┌──────────────────────────────────────────────────────────┐
│                    FlysystemService                       │
├──────────────────────────────────────────────────────────┤
│                                                           │
│  store(key, content)                                      │
│    ├── Write file → var/storage/cache/{key}               │
│    ├── Create/update FileCache entity in DB               │
│    │   - key, path, hash (sha256), size, expiresAt        │
│    └── Emit FileCacheStored domain event                  │
│                                                           │
│  retrieve(key)                                            │
│    ├── Find FileCache entity by key                       │
│    ├── Check expiration → invalidate if expired           │
│    └── Read file from Flysystem                           │
│                                                           │
│  invalidate(key)                                          │
│    ├── Delete file from Flysystem                         │
│    ├── Remove FileCache entity from DB                    │
│    └── Emit FileCacheInvalidated domain event             │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

### 20.3 FileCache Entity

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID | Primary key |
| `key` | string(255) | Unique cache key |
| `path` | string(512) | Flysystem path |
| `hash` | string(64) | SHA-256 checksum |
| `size` | integer | File size in bytes |
| `expiresAt` | datetime\|null | Expiration (null = never) |
| `createdAt` | datetime | Creation timestamp |
| `updatedAt` | datetime | Last update timestamp |

### 20.4 Storage Location

Files stored in `var/storage/cache/` (gitignored). Default adapter: `LocalFilesystemAdapter`.

```
var/storage/
└── cache/
    ├── user_avatar_123
    ├── report_2026_04_06
    └── export_users_csv
```

### 20.5 Events

| Event | When | Handler |
|-------|------|---------|
| `FileCacheStored` | After `store()` | Logs key, path, size |
| `FileCacheInvalidated` | After `invalidate()` | Logs key, path |

---

## 21. Domain Event Bus

**Doctrine lifecycle → domain events → async handlers.**

### 21.1 How It Works

```
Doctrine Entity Operation
         ↓
┌─────────────────────────────┐
│  DoctrineEventSubscriber    │
│  (listens to Doctrine)      │
│  - postPersist              │
│  - postUpdate               │
│  - postRemove               │
└────────────┬────────────────┘
             ↓
┌─────────────────────────────┐
│    DomainEventEmitter       │
│  emit()  → sync dispatch    │
│  emitAsync() → async queue  │
└────────────┬────────────────┘
             ↓
┌─────────────────────────────┐
│   Symfony EventDispatcher   │
│   (registered handlers)     │
└─────────────────────────────┘
```

### 21.2 Domain Events

| Event | Triggered On | Data |
|-------|-------------|------|
| `EntityCreated` | `postPersist` | entityClass, entityId, full entity data |
| `EntityUpdated` | `postUpdate` | entityClass, entityId, change set |
| `EntityDeleted` | `postRemove` | entityClass, entityId |

### 21.3 Creating a Handler

```php
use App\Event\DomainEvent;
use App\Event\DomainEventHandler;
use App\Event\EntityCreated;

class UserCreatedHandler implements DomainEventHandler
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof EntityCreated) {
            return;
        }

        if ($event->getEntityClass() !== \App\Entity\User::class) {
            return;
        }

        $userId = $event->getEntityId();
        $data = $event->getData();
        // Send welcome email, create audit log, etc.
    }
}
```

### 21.4 Registering a Handler

```php
// In your service provider or bootstrap
use App\Event\EntityCreated;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

$dispatcher->addListener(EntityCreated::class, [new UserCreatedHandler(), 'handle']);
```

### 21.5 Async Handling

```php
// DomainEventEmitter supports both sync and async
$emitter->emit($event);      // Immediate, blocking
$emitter->emitAsync($event); // Queued for background processing

// Async events prefixed with 'async.' in dispatcher
$dispatcher->addListener('async.' . EntityCreated::class, [
    new AsyncUserCreatedHandler(),
    'handle'
]);
```

### 21.6 Event Payload Structure

```php
// EntityCreated
[
    'entityClass' => 'App\\Entity\\User',
    'entityId' => '550e8400-e29b-41d4-a716-446655440000',
    'data' => [
        'email' => 'user@example.com',
        'createdAt' => '2026-04-06T10:00:00+00:00',
        // ... other scalar properties
    ],
    'occurredOn' => '2026-04-06T10:00:00+00:00',
]

// EntityUpdated
[
    'entityClass' => 'App\\Entity\\User',
    'entityId' => '550e8400-e29b-41d4-a716-446655440000',
    'changes' => [
        'email' => ['old@example.com', 'new@example.com'],
        'updatedAt' => [null, '2026-04-06T10:00:00+00:00'],
    ],
    'occurredOn' => '2026-04-06T10:00:00+00:00',
]
```

---

## 22. Async Monolog Worker

**Background processing for async events with failure logging.**

### 22.1 How It Works

```
DomainEventEmitter::emitAsync(event)
        ↓
AsyncEventBus.spawnSubprocess(event)
        ↓
exec("php bin/async-event-worker.php <base64_payload>")  // fire-and-forget
        ↓
[bin/async-event-worker.php]
        ├── Bootstrap minimal app (EntityManager, EventDispatcher)
        ├── Deserialize event from argv[1]
        ├── Dispatch to 'async.' . event class name
        └── Log failures to var/log/async_failures.log
```

### 22.2 Quick Start

```php
use App\Event\DomainEventEmitter;

// Emit sync (same request)
$emitter->emit($event);

// Emit async (background process)
$emitter->emitAsync($event);
```

### 22.3 Creating an Async Handler

```php
use App\Event\DomainEvent;
use App\Event\DomainEventHandler;
use App\Event\EntityCreated;

class AsyncUserCreatedHandler implements DomainEventHandler
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof EntityCreated) {
            return;
        }

        if ($event->getEntityClass() !== \App\Entity\User::class) {
            return;
        }

        // Heavy processing that shouldn't block request:
        // - Send welcome email (queued)
        # - Generate thumbnails
        # - Update search index
        # - Call external APIs
    }
}
```

### 22.4 Registering Async Handlers

```php
// In your service provider or bootstrap
use App\Event\EntityCreated;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// Handler registered for async.* events
$dispatcher->addListener('async.' . EntityCreated::class, [
    new AsyncUserCreatedHandler(),
    'handle'
]);
```

### 22.5 Failure Handling

Async event failures are logged to `var/log/async_failures.log` with:
- Full exception message and traceback
- Event type and serialized payload
- Timestamp with UID for correlation
- The worker exits with code 1 on failure (logged but doesn't block main request)

### 22.6 Log Files

| Log File | Purpose | Level |
|----------|---------|-------|
| `var/log/app_{env}.log` | ORM events via ORMEventListener | debug, info |
| `var/log/async_failures.log` | Failed async events | error |

---

## 23. File Cache Quick Starter

**Get started with the hybrid entity-backed file cache in 60 seconds.**

### 23.1 Installation & Setup

1. **Ensure Flysystem is installed** (already in composer.json):
   ```bash
   composer require league/flysystem
   ```

2. **Create storage directory** (gitignored):
   ```bash
   mkdir -p var/storage/cache
   chmod 755 var/storage/cache
   ```

3. **Run migrations** to create `file_cache` table:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

### 23.2 Basic Usage

```php
use App\Service\FlysystemService;

$fileService = $container->get(FlysystemService::class);

// Store a file with metadata
$fileCache = $fileService->store(
    key: 'user_avatar_123',
    content: $fileContents,
    expiresAt: new \DateTimeImmutable('+30 days')
);

// Retrieve file contents
$contents = $fileService->retrieve('user_avatar_123');

// Check if file exists and is not expired
if ($fileService->exists('user_avatar_123')) {
    // File is ready to use
}

// Get metadata only (doesn't read file)
$metadata = $fileService->getMetadata('user_avatar_123');
echo "Size: {$metadata->getSize()} bytes";
echo "SHA-256: {$metadata->getHash()}";

// Invalidate cache (deletes file + DB record)
$fileService->invalidate('user_avatar_123');
```

### 23.3 Advanced Usage

**With expiration:**
```php
// Expires in 1 hour
$fileService->store('temp_report_456', $reportData, new \DateTimeImmutable('+1 hour'));

// Never expires
$fileService->store('permanent_config_789', $configData);
```

**Error handling:**
```php
try {
    $fileService->store('important_file', $data);
} catch (\RuntimeException $e) {
    // Handle storage failures (disk full, permissions, etc.)
    $logger->error('File storage failed', ['error' => $e->getMessage()]);
}
```

**Event integration:**
```php
use App\Event\FileCacheStored;
use App\Event\FileCacheInvalidated;

// Listen to file cache events
$dispatcher->addListener(FileCacheStored::class, [$handler, 'onFileCacheStored']);
$dispatcher->addListener(FileCacheInvalidated::class, [$handler, 'onFileCacheInvalidated']);
```

### 23.4 Storage Structure

Files are stored in `var/storage/cache/` with safe filenames:
```
var/storage/
└── cache/
    ├── user_avatar_123
    ├── report_2026_04_06
    ├── temp_export_xyz
    └── permanent_config_789
```

Filenames are URL-safe versions of the cache key (slashes/backslashes replaced with underscores).

### 23.5 Database Schema

The `file_cache` table is created automatically via migrations:
```sql
CREATE TABLE file_cache (
    id CHAR(36) PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    path VARCHAR(512) NOT NULL,
    hash CHAR(64),
    size INTEGER NOT NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

---

## 24. .gitignore Quick Reference

**Essential ignores for development and production.**

### 24.1 Directories

```
/vendor/                 # Composer dependencies
/var/log/               # Application logs
/var/storage/           # Flysystem file storage
/var/data/              # SQLite databases and data files
reports/                # Test coverage reports
coverage/               # PHPUnit coverage output
.idea/                  # JetBrains IDE settings
.vscode/                # VS Code settings
*.swp                   # Vim swap files
*.log                   # Log files
```

### 24.2 Files

```
composer.lock           # Lock file for reproducible builds
.php-cs-fixer.cache     # PHP-CS-Fixer cache
.php-cs-fixer.php       # PHP-CS-Fixer config
phpunit.xml             # PHPUnit configuration
.phpactor.json          # PHP Actor configuration
/var/data/orm.db        # SQLite ORM database
.env.yaml.local         # Local environment overrides
```

### 24.3 PWA Assets (User-Managed)

```
/public/manifest.json   # PWA manifest
/public/sw.js           # Service worker
/public/icons/          # PWA icons
```

### 24.4 Version Control

```
auth.json               # Private repository auth
```

### 24.5 Why These Are Ignored

- **Dependencies**: `/vendor/` managed by Composer
- **Logs**: `/var/log/` contains runtime application logs
- **Storage**: `/var/storage/` contains user-uploaded/cached files
- **Data**: `/var/data/` contains SQLite databases
- **IDE Configs**: Personal editor settings
- **Cache Files**: Tool-generated cache files
- **Lock Files**: `composer.lock` for reproducible builds
- **Environment**: Local overrides should not be committed
- **PWA Assets**: Users should customize manifests and service workers
- **Auth**: Private credentials for paid packages

---

## 25. Quick Start (SQLite, 30 seconds)

**Get a development environment running in under 30 seconds.**

### 25.1 Prerequisites

- PHP 8.2+ with SQLite extension
- Composer 2.0+

### 25.2 Installation

```bash
# 1. Clone repository
git clone <repository-url>
cd orm-develop

# 2. Install dependencies
composer install

# 3. Copy environment template
cp .env.dist .env

# 4. Create required directories
mkdir -p var/log var/storage/cache var/data

# 5. Run migrations
php bin/console doctrine:migrations:migrate

# 6. Load test data (optional)
php bin/console doctrine:fixtures:load
```

### 25.3 Development Server

```bash
# Start PHP built-in server
php -S localhost:8080 -t public

# Visit: http://localhost:8080
```

### 25.4 Available Endpoints

| Path | Method | Description |
|------|--------|-------------|
| `/` | GET | Home page with system info |
| `/api/users` | GET | List users (JSON:HAL) |
| `/api/users/{id}` | GET | Get user details |
| `/mvc/users` | GET | MVC user list page |
| `/mvc/users/create` | GET/POST | Create user form |
| `/mvc/groups` | GET | MVC Baltimore groups page |

### 25.5 Console Commands

| Command | Description |
|---------|-------------|
| `php bin/console` | List all available commands |
| `php bin/console doctrine:migrations:migrate` | Run database migrations |
| `php bin/console doctrine:fixtures:load` | Load test fixtures |
| `php bin/console cache:status` | Check cache status |
| `php bin/console cache:clear` | Clear application cache |
| `php bin/console benchmark:run` | Run performance benchmarks |

### 25.6 Testing

```bash
# Run all tests
composer test

# Run specific test suite
vendor/bin/phpunit tests/Unit/EntityManagerTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html reports/coverage
```

### 25.7 Troubleshooting

**Permission denied on var/storage/:**
```bash
chmod -R 755 var/storage/
chown -R $USER:$USER var/storage/
```

**SQLite database locked:**
```bash
# Check for stale processes
lsof var/data/orm.db
# Kill conflicting processes or restart
```

**Missing dependencies:**
```bash
composer install --no-interaction --prefer-dist
```

---

## 26. Summary

| Layer | Pattern | HTTP | Templates | Dependencies |
|-------|---------|------|-----------|---------------|
| **MVC** | Controller → Model → View | Vanilla PHP | PHP | Doctrine ORM |
| **ADR** | Action → Domain → Responder | laminas/diactoros | JSON:HAL | League Fractal |
| **PWA** | Service Worker + Manifest | Both | Cache | Offline-first |

**Key Files:**
```
├── public/
│   ├── index.php      # Unified entry (routes MVC ↔ ADR)
│   ├── mvc.php        # MVC only
│   └── api.php        # ADR only
├── src/
│   ├── Http/          # Vanilla MVC HTTP (no dependencies)
│   ├── Controller/    # MVC Controllers
│   ├── View/          # PHP Templates
│   ├── Action/        # ADR Actions
│   ├── Responder/     # JSON:HAL Responders
│   ├── Transformer/   # League Fractal Transformers
│   ├── Entity/        # Doctrine Entities (12)
│   │   ├── User.php
│   │   ├── Group.php          # STI base (hidden from API)
│   │   ├── DeveloperGroup.php # STI
│   │   ├── DesignerGroup.php  # STI
│   │   ├── TesterGroup.php    # STI
│   │   ├── Role.php           # STI base (hidden from API)
│   │   ├── WizardRole.php     # STI
│   │   ├── ArchitectRole.php  # STI
│   │   ├── GameMasterRole.php # STI
│   │   ├── UserRole.php       # join entity
│   │   └── UserGroup.php      # join entity
│   └── Fixture/       # League Factory Muffin
├── schema/            # Doctrine XML mappings
│   ├── User.orm.xml
│   ├── Group.orm.xml        # STI mapping
│   ├── UserGroup.orm.xml    # Many-to-many
│   ├── Role.orm.xml
│   ├── UserRole.orm.xml
│   ├── DeveloperGroup.orm.xml
│   ├── DesignerGroup.orm.xml
│   └── TesterGroup.orm.xml
├── templates/         # MVC PHP templates
│   ├── home.php
│   ├── users/
│   └── error/
└── tests/
    ├── Action/        # ADR Action Tests
    ├── Unit/          # Unit Tests
    │   ├── GroupInheritanceTest.php  # STI drill tests
    │   ├── UserRolesCollectionTest.php
    │   ├── DoctrineCollectionAdvancedTest.php
    │   └── RoleCollectionTest.php
    └── factories/     # Factory Definitions
```

---

*"The best architecture is the one that fits your needs."* — Unknown
