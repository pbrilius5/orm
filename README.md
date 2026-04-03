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
9. [Fixtures](#9-fixtures)
10. [Middleware Security](#10-middleware-security)
11. [Environment Configuration](#11-environment-configuration)
12. [XML Schema-Driven Entity Generation](#12-xml-schema-driven-entity-generation)
13. [Role-Based Access su Doctrine Collections](#13-role-based-access-su-doctrine-collections)
14. [Summary](#14-summary)

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

# 4. Create database and schema from XML
bin/console oryx:db:create

# 5. Load demo fixtures (teams, roles, users, wands, patronuses)
bin/console oryx:fixtures:load

# 6. Start the server
composer serve
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
| [http://localhost:8080/api/users?include=posts,group](http://localhost:8080/api/users?include=posts,group) | With embedded relations |
| [http://localhost:8080/api/users?include=userRoles,wands,patronuses](http://localhost:8080/api/users?include=userRoles,wands,patronuses) | With role system |

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
| `bin/console oryx:fixtures:load` | Load demo fixtures using Faker |
| `bin/console orm:generate:entities` | Generate entity classes from XML schema |

### Database Creation

```bash
# Create SQLite database (default)
bin/console oryx:db:create

# Force recreate (drops existing)
bin/console oryx:db:create --force
```

### Fixtures Loading

```bash
# Default: 3 groups, 10 users, 2 posts per user
bin/console oryx:fixtures:load

# Custom counts
bin/console oryx:fixtures:load --groups=5 --users=50 --posts=3

# Purge existing data first
bin/console oryx:fixtures:load --purge

# Reproducible random data
bin/console oryx:fixtures:load --seed=42
```

| Option | Default | Description |
|--------|---------|-------------|
| `--groups` | 3 | Number of groups |
| `--users` | 10 | Number of users |
| `--posts` | 2 | Posts per user |
| `--purge` | — | Purge data before loading |
| `--seed` | null | Random seed |

### Entity Generation from XML

```bash
# Generate all entities
bin/console orm:generate:entities

# Generate specific entity
bin/console orm:generate:entities --filter=User
bin/console orm:generate:entities --filter='App\Entity\Post'
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
│  │ • App\Http\Response│        │ • App\Action\User\*     │   │
│  │ • App\Http\Router │        │ • League\Fractal        │   │
│  │ • PHP Templates   │        │ • JsonHalResponder       │   │
│  └─────────────────────┘        └─────────────────────────┘   │
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
        $this->router->map('GET', '/health', fn() => new JsonResponse(['status' => 'ok']));
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

use App\Entity\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['posts', 'group', 'userRoles', 'wands', 'patronuses'];

    public function transform(User $user): array
    {
        $roles = [];
        foreach ($user->getUserRoles() as $userRole) {
            if ($userRole->isActive()) {
                $roles[] = $userRole->getRole()->getName();
            }
        }

        return [
            'id' => $user->getId() ?? 0,
            'email' => $user->getEmail(),
            'roles' => $roles,
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()?->format('c'),
        ];
    }

    public function includePosts(User $user)
    {
        return $this->collection($user->getPosts(), new PostTransformer());
    }

    public function includeGroup(User $user)
    {
        return $this->item($user->getGroup(), new GroupTransformer());
    }

    public function includeUserRoles(User $user)
    {
        return $this->collection($user->getUserRoles(), new UserRoleTransformer());
    }

    public function includeWands(User $user)
    {
        return $this->collection($user->getWands(), new WandTransformer());
    }

    public function includePatronuses(User $user)
    {
        return $this->collection($user->getPatronuses(), new PatronusTransformer());
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

## 9. Fixtures

**Fixtures provide test data generation.**

### 9.1 Factory Definition (FactoryMuffin)

```php
// tests/factories/user.factories.php
use App\Entity\User;
use App\Entity\Team;

$fm->define(User::class)->setDefinitions([
    'email' => 'user{++}@wizardplatform.com',
    'password' => 'password123',
    'createdAt' => fn() => new \DateTimeImmutable(),
    'updatedAt' => null,
    'team' => 'factory|' . Team::class,
]);

// tests/factories/role.factories.php
use App\Entity\Role;

$fm->define(Role::class)->setDefinitions([
    'name' => fn() => $fm->random([Role::WIZARD, Role::ARCHITECT, Role::GAME_MASTER]),
    'description' => fn() => 'Magic role for wizard platform',
]);

// tests/factories/team.factories.php
use App\Entity\Team;

$fm->define(Team::class)->setDefinitions([
    'name' => 'Team {++}',
    'description' => fn() => 'Demo team created by faker',
    'createdAt' => fn() => new \DateTimeImmutable(),
]);

// tests/factories/user_role.factories.php
use App\Entity\UserRole;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\Team;

$fm->define(UserRole::class)->setDefinitions([
    'user' => 'factory|' . User::class,
    'role' => 'factory|' . Role::class,
    'team' => 'factory|' . Team::class,
    'grantedAt' => fn() => new \DateTimeImmutable(),
    'expiresAt' => null,
]);
```

### 9.2 FixtureLoader

```php
// src/Fixture/FixtureLoader.php
namespace App\Fixture;

use League\FactoryMuffin\FactoryMuffin;

class FixtureLoader
{
    private FactoryMuffin $fm;

    public function __construct()
    {
        $this->fm = new FactoryMuffin(null, null);
        $this->fm->loadFactories(__DIR__ . '/../../tests/factories');
    }

    public function make(string $class): object
    {
        return $this->fm->seed(1, $class, [], false)[0];
    }

    public function makeMany(string $class, int $count): array
    {
        return $this->fm->seed($count, $class, [], false);
    }
}
```

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
 
## 11. PWA Support

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
 
## 12. Environment Configuration

### 11.1 Configuration Files

```
.env.dist          # Template defaults (committed to VCS) - all variables documented here
.env.yaml          # Primary YAML config (RECOMMENDED)
.env               # Legacy override (optional, gitignored)
.env.yaml.local    # Local overrides (optional, gitignored)
```

### 11.2 Setup Instructions

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

### 11.3 Loading Priority

1. **System environment variables** - `$_ENV`, `$_SERVER`
2. **`.env` file** - Legacy KEY=VALUE format (if present)
3. **`.env.yaml`** - Primary YAML configuration
4. **`.env.dist`** - Template defaults

### 11.3 YAML Configuration Format

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

### 11.4 Variable Substitution Syntax

| Syntax | Description | Example |
|--------|-------------|---------|
| `${VAR}` | Direct reference | `${DB_HOST}` |
| `${VAR:-default}` | Default if not set | `${DB_HOST:-localhost}` |
| `${VAR:?error}` | Error if not set | `${DB_PASSWORD:?Required}` |
| `${nested.key}` | Nested reference | `${database.host}` |

### 11.5 Using EnvironmentConfig in Code

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

### 11.6 Environment Variables Reference

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
| `CACHE_DRIVER` | Cache driver (array/memcached) | `array` | No |
| `CACHE_HOST` | Cache server hostname | `localhost` | No |
| `CACHE_PORT` | Cache server port | `11211` | No |
| `CACHE_TTL` | Cache time-to-live (seconds) | `3600` | No |
| `RATE_LIMIT_ENABLED` | Enable rate limiting | `true` | No |
| `RATE_LIMIT_MAX_REQUESTS` | Max requests per window | `60` | No |
| `RATE_LIMIT_WINDOW` | Rate limit window (seconds) | `60` | No |
| `MEMCACHED_HOST` | Memcached hostname | `localhost` | No |
| `MEMCACHED_PORT` | Memcached port | `11211` | No |
| `MAILER_TRANSPORT` | Mailer transport | `smtp` | No |
| `MAILER_HOST` | Mailer hostname | `localhost` | No |
| `MAILER_PORT` | Mailer port | `25` | No |
| `MAILER_USER` | Mailer username | (empty) | No |
| `MAILER_PASSWORD` | Mailer password | (empty) | No |

---

## 13. XML Schema-Driven Entity Generation

### 12.1 Schema Location

All Doctrine XML mappings live in `/schema`:

```
schema/
├── User.orm.xml
├── Post.orm.xml
└── Group.orm.xml
```

### 12.2 Pipeline

```
schema/*.orm.xml → bin/console orm:generate:entities → src/Entity/*.php
```

### 12.3 Example Schema

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
        
        <many-to-one target-entity="App\Entity\Team" field="team" inversed-by="users">
            <join-column name="team_id" nullable="true"/>
        </many-to-one>
        
        <one-to-many target-entity="App\Entity\Post" field="posts" mapped-by="author" cascade="persist"/>
        <one-to-many target-entity="App\Entity\UserRole" field="userRoles" mapped-by="user" cascade="persist" orphan-removal="true"/>
        <one-to-many target-entity="App\Entity\Wand" field="wands" mapped-by="user" cascade="persist"/>
        <one-to-many target-entity="App\Entity\Patronus" field="patronuses" mapped-by="user" cascade="persist"/>
        <one-to-many target-entity="App\Entity\InvisibilityCloak" field="invisibilityCloaks" mapped-by="user" cascade="persist"/>
    </entity>
</doctrine-mapping>
```

---

## 14. Role-Based Access su Doctrine Collections

**Wizard Platform role system su privalomu ROLE_WIZARD ir organizaciniu scope.**

### 13.1 Schema Overview

| Entity | Table | Description |
|--------|-------|-------------|
| `Role` | `roles` | Wizard roles: WIZARD, ARCHITECT, GAME_MASTER |
| `UserRole` | `user_roles` | VIA lentelė: user_id + role_id + team_id |
| `Team` | `teams` | Game dev teams (Level Design, Character Art, Audio) |
| `Wand` | `wands` | Permission token su JSON permissions |
| `Patronus` | `patronuses` | JWT-like token su expiration |
| `InvisibilityCloak` | `invisibility_cloaks` | Invisible privilege per team |

### 13.2 Doctrine Collection vs Array

**Senas būdas (array):**
```php
// User::$roles - JSON laukas
$user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
```

**Naujas būdas (Collection):**
```php
// User::$userRoles - Doctrine Collection<UserRole>
$roles = $user->getUserRoles(); // Returns Collection
$roles->filter(fn($ur) => $ur->isActive());
$roles->map(fn($ur) => $ur->getRole()->getName());
```

**Collection privalumai:**
- Type-safe (Collection<UserRole>)
- Lazy loading (neuzkrauna visų iš karto)
- Filtering/mapping be papildomų užklausų
- Relations su kitais entity

### 13.3 Privalomo ROLE_WIZARD Logika

Pridedant bet kokią kitą rolę, automatiškai pridedamas ROLE_WIZARD:

```php
// src/Entity/User.php
public function addRole(Role $role, Team $team): self
{
    // Check if role already exists
    foreach ($this->userRoles as $existingUserRole) {
        if ($existingUserRole->getRole() === $role && $existingUserRole->getTeam() === $team) {
            return $this;
        }
    }

    $userRole = new UserRole();
    $userRole->setUser($this);
    $userRole->setRole($role);
    $userRole->setTeam($team);
    $this->userRoles->add($userRole);
    $role->addUserRole($userRole);

    // Auto-grant WIZARD if adding other role
    if ($role->getName() !== Role::WIZARD && !$this->hasRole(Role::WIZARD, $team)) {
        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);
        $wizardRole->setTeam($team);
        $this->addRole($wizardRole, $team);
    }

    return $this;
}
```

### 13.4 Cross-Team Roles

Vartotojas gali turėti skirtingas roles skirtingose team:

```php
// User turi ARCHITECT role Level Design team
$user->addRole($architectRole, $levelDesignTeam);

// User turi GAME_MASTER role Character Art team
$user->addRole($gameMasterRole, $characterArtTeam);

// Tikrina role konkrečioje team
$user->hasRole(Role::ARCHITECT, $levelDesignTeam); // true
$user->hasRole(Role::ARCHITECT, $characterArtTeam); // false

// Gauna visus roles
$allRoles = $user->getAllRoles(); // [WIZARD, ARCHITECT, GAME_MASTER]

// Gauna roles konkrečiai team
$teamRoles = $user->getRolesForTeam($levelDesignTeam); // [WIZARD, ARCHITECT]
```

### 13.5 API Pavyzdžiai su Role System

**GET /api/users/1?include=userRoles,wands,patronuses**

```json
{
  "_links": {
    "self": { "href": "/api/users/1" },
    "collection": { "href": "/api/users" }
  },
  "_embedded": {
    "user": {
      "id": 1,
      "email": "harry@wizardplatform.com",
      "roles": ["ROLE_WIZARD", "ROLE_ARCHITECT"],
      "created_at": "2026-04-02T10:00:00+02:00",
      "updated_at": null
    },
    "userRoles": [
      {
        "id": 1,
        "role": "ROLE_WIZARD",
        "team": "Level Design",
        "granted_at": "2026-04-02T10:00:00+02:00",
        "expires_at": null,
        "is_active": true
      },
      {
        "id": 2,
        "role": "ROLE_ARCHITECT",
        "team": "Level Design",
        "granted_at": "2026-04-02T10:05:00+02:00",
        "expires_at": null,
        "is_active": true
      }
    ],
    "wands": [
      {
        "id": 1,
        "name": "Wand of Power",
        "role": "ROLE_WIZARD",
        "permissions": ["read", "write"],
        "created_at": "2026-04-02T10:00:00+02:00",
        "expires_at": null,
        "is_active": true
      }
    ],
    "patronuses": [
      {
        "id": 1,
        "token": "a1b2c3d4e5f6...",
        "role": "ROLE_WIZARD",
        "team": "Level Design",
        "issued_at": "2026-04-02T10:00:00+02:00",
        "expires_at": "2026-04-03T10:00:00+02:00",
        "is_valid": true
      }
    ]
  }
}
```

**GET /api/users/1?include=userRoles**

```json
{
  "_links": {
    "self": { "href": "/api/users/1" },
    "collection": { "href": "/api/users" }
  },
  "_embedded": {
    "user": {
      "id": 1,
      "email": "harry@wizardplatform.com",
      "roles": ["ROLE_WIZARD", "ROLE_ARCHITECT"],
      "created_at": "2026-04-02T10:00:00+02:00"
    },
    "userRoles": [
      {
        "id": 1,
        "role": "ROLE_WIZARD",
        "team": "Level Design",
        "granted_at": "2026-04-02T10:00:00+02:00",
        "expires_at": null,
        "is_active": true
      },
      {
        "id": 2,
        "role": "ROLE_ARCHITECT",
        "team": "Level Design",
        "granted_at": "2026-04-02T10:05:00+02:00",
        "expires_at": null,
        "is_active": true
      }
    ]
  }
}
```

### 13.6 Collection Operations

```php
// Gauti visus aktyvius roles (Collection filter + map)
$activeRoles = $user->getAllRoles();

// Gauti role names kaip array
$roleNames = $user->getRoleNames();

// Gauti roles konkrečiai team
$teamRoles = $user->getRolesForTeam($team);

// Gauti expired wands
$expiredWands = $user->getExpiredWands();

// Gauti active wands
$activeWands = $user->getActiveWands();

// Gauti valid patronuses
$validPatronuses = $user->getValidPatronuses();

// Gauti active invisibility cloaks
$activeCloaks = $user->getActiveInvisibilityCloaks();

// Tikrina ar turi specifinę rolę
$hasArchitect = $user->hasRole(Role::ARCHITECT, $team);

// Tikrina ar turi validų patronus
$hasValidPatronus = $user->hasValidPatronusInTeam($team);

// Tikrina ar yra nematomas team
$isInvisible = $user->isInvisibleInTeam($team);

// Team user count
$userCount = $team->countUsers();
```

### 13.7 Schema Files

```
schema/
├── User.orm.xml
├── Role.orm.xml
├── UserRole.orm.xml
├── Team.orm.xml
├── Wand.orm.xml
├── Patronus.orm.xml
├── InvisibilityCloak.orm.xml
├── Post.orm.xml
└── Group.orm.xml
```

### 13.8 TDD su Doctrine Collections

**Test-Driven Development rodo evoliucinį dizainą** - testai rašomi pirmiausia, tada implementacija, tada refaktoringas.

#### 13.8.1 RED - Pirmas testas (failina)

```php
// tests/Unit/UserRolesCollectionTest.php
public function testGetAllRolesReturnsOnlyActive(): void
{
    $user = new User();
    $user->setEmail('test@example.com');
    $user->setPassword('password123');

    $team = new Team();
    $team->setName('Level Design');
    $team->setCreatedAt(new \DateTimeImmutable());

    $wizardRole = new Role();
    $wizardRole->setName(Role::WIZARD);

    $architectRole = new Role();
    $architectRole->setName(Role::ARCHITECT);

    // Expired role
    $expiredUserRole = new UserRole();
    $expiredUserRole->setRole($wizardRole);
    $expiredUserRole->setTeam($team);
    $expiredUserRole->setGrantedAt(new \DateTimeImmutable('-2 days'));
    $expiredUserRole->setExpiresAt(new \DateTimeImmutable('-1 day'));

    // Active role
    $activeUserRole = new UserRole();
    $activeUserRole->setRole($architectRole);
    $activeUserRole->setTeam($team);
    $activeUserRole->setGrantedAt(new \DateTimeImmutable());

    $user->getUserRoles()->add($expiredUserRole);
    $user->getUserRoles()->add($activeUserRole);

    $activeRoles = $user->getAllRoles();

    $this->assertCount(1, $activeRoles);
    $this->assertSame(Role::ARCHITECT, $activeRoles[0]->getName());
}
```

**Testas failina** nes `getAllRoles()` metodas dar neegzistuoja.

#### 13.8.2 GREEN - Pirmas implementacija (foreach)

```php
// src/Entity/User.php
public function getAllRoles(): array
{
    $roles = [];
    foreach ($this->userRoles as $userRole) {
        if ($userRole->isActive()) {
            $roles[] = $userRole->getRole();
        }
    }
    return $roles;
}
```

**Testas praeina** ✅

#### 13.8.3 REFACTOR - Collection API (filter + map)

```php
// src/Entity/User.php
public function getAllRoles(): array
{
    return array_values($this->userRoles
        ->filter(fn($ur) => $ur->isActive())
        ->map(fn($ur) => $ur->getRole())
        ->toArray());
}
```

**Testas vis dar praeina** ✅ - bet kodas elegantiškesnis.

#### 13.8.4 Antras testas - getRoleNames()

```php
public function testGetRoleNamesUsesCollectionMap(): void
{
    $user = new User();
    $user->setEmail('mapper@example.com');
    $user->setPassword('password123');

    $team = new Team();
    $team->setName('Character Art');
    $team->setCreatedAt(new \DateTimeImmutable());

    $wizardRole = new Role();
    $wizardRole->setName(Role::WIZARD);

    $gameMasterRole = new Role();
    $gameMasterRole->setName(Role::GAME_MASTER);

    $wizardUserRole = new UserRole();
    $wizardUserRole->setRole($wizardRole);
    $wizardUserRole->setTeam($team);
    $wizardUserRole->setGrantedAt(new \DateTimeImmutable());

    $gmUserRole = new UserRole();
    $gmUserRole->setRole($gameMasterRole);
    $gmUserRole->setTeam($team);
    $gmUserRole->setGrantedAt(new \DateTimeImmutable());

    $user->getUserRoles()->add($wizardUserRole);
    $user->getUserRoles()->add($gmUserRole);

    $roleNames = $user->getRoleNames();

    $this->assertCount(2, $roleNames);
    $this->assertContains(Role::WIZARD, $roleNames);
    $this->assertContains(Role::GAME_MASTER, $roleNames);
}
```

**Implementacija:**
```php
public function getRoleNames(): array
{
    return array_values($this->userRoles
        ->filter(fn($ur) => $ur->isActive())
        ->map(fn($ur) => $ur->getRole()->getName())
        ->toArray());
}
```

#### 13.8.5 Trečias testas - Cross-team roles

```php
public function testGetRolesForTeamReturnsOnlyMatchingRoles(): void
{
    $user = new User();
    $user->setEmail('cross@example.com');
    $user->setPassword('password123');

    $teamA = new Team();
    $teamA->setName('Level Design');
    $teamA->setCreatedAt(new \DateTimeImmutable());

    $teamB = new Team();
    $teamB->setName('Audio Engineering');
    $teamB->setCreatedAt(new \DateTimeImmutable());

    $wizardRole = new Role();
    $wizardRole->setName(Role::WIZARD);

    $architectRole = new Role();
    $architectRole->setName(Role::ARCHITECT);

    $userRoleA = new UserRole();
    $userRoleA->setRole($wizardRole);
    $userRoleA->setTeam($teamA);
    $userRoleA->setGrantedAt(new \DateTimeImmutable());

    $userRoleB = new UserRole();
    $userRoleB->setRole($architectRole);
    $userRoleB->setTeam($teamB);
    $userRoleB->setGrantedAt(new \DateTimeImmutable());

    $user->getUserRoles()->add($userRoleA);
    $user->getUserRoles()->add($userRoleB);

    $teamARoles = $user->getRolesForTeam($teamA);

    $this->assertCount(1, $teamARoles);
    $this->assertSame(Role::WIZARD, $teamARoles[0]->getName());
}
```

**Implementacija:**
```php
public function getRolesForTeam(Team $team): array
{
    return array_values($this->userRoles
        ->filter(fn($ur) => $ur->getTeam() === $team && $ur->isActive())
        ->map(fn($ur) => $ur->getRole())
        ->toArray());
}
```

#### 13.8.6 Ketvirtas testas - Collection count

```php
public function testCollectionCountReturnsCorrectNumber(): void
{
    $user = new User();
    $user->setEmail('count@example.com');
    $user->setPassword('password123');

    $this->assertInstanceOf(Collection::class, $user->getUserRoles());
    $this->assertCount(0, $user->getUserRoles());

    $team = new Team();
    $team->setName('Test Team');
    $team->setCreatedAt(new \DateTimeImmutable());

    $role = new Role();
    $role->setName(Role::WIZARD);

    $userRole = new UserRole();
    $userRole->setRole($role);
    $userRole->setTeam($team);
    $userRole->setGrantedAt(new \DateTimeImmutable());

    $user->getUserRoles()->add($userRole);

    $this->assertCount(1, $user->getUserRoles());
}
```

#### 13.8.7 Pilnas TDD ciklas - Wand operations

```php
public function testGetExpiredWandsReturnsOnlyExpired(): void
{
    $user = new User();
    $user->setEmail('wand@example.com');
    $user->setPassword('password123');

    $role = new Role();
    $role->setName(Role::WIZARD);

    $expiredWand = new Wand();
    $expiredWand->setUser($user);
    $expiredWand->setRole($role);
    $expiredWand->setName('Expired Wand');
    $expiredWand->setPermissions(json_encode(['read']));
    $expiredWand->setCreatedAt(new \DateTimeImmutable('-2 days'));
    $expiredWand->setExpiresAt(new \DateTimeImmutable('-1 day'));

    $activeWand = new Wand();
    $activeWand->setUser($user);
    $activeWand->setRole($role);
    $activeWand->setName('Active Wand');
    $activeWand->setPermissions(json_encode(['read', 'write']));
    $activeWand->setCreatedAt(new \DateTimeImmutable());

    $user->getWands()->add($expiredWand);
    $user->getWands()->add($activeWand);

    $expiredWands = $user->getExpiredWands();
    $activeWands = $user->getActiveWands();

    $this->assertCount(1, $expiredWands);
    $this->assertCount(1, $activeWands);
    $this->assertSame('Expired Wand', $expiredWands[0]->getName());
    $this->assertSame('Active Wand', $activeWands[0]->getName());
}
```

**Implementacija:**
```php
public function getExpiredWands(): array
{
    return array_values($this->wands
        ->filter(fn($wand) => $wand->isExpired())
        ->toArray());
}

public function getActiveWands(): array
{
    return array_values($this->wands
        ->filter(fn($wand) => !$wand->isExpired())
        ->toArray());
}
```

#### 13.8.8 TDD Summary

| Žingsnis | Testas | Implementacija | Rezultatas |
|----------|--------|----------------|------------|
| 1. RED | `testGetAllRolesReturnsOnlyActive` | Method doesn't exist | ❌ Fail |
| 2. GREEN | Same test | `foreach` loop | ✅ Pass |
| 3. REFACTOR | Same test | `filter()` + `map()` | ✅ Pass |
| 4. RED | `testGetRoleNamesUsesCollectionMap` | Method doesn't exist | ❌ Fail |
| 5. GREEN | Same test | `filter()` + `map()` + `getName()` | ✅ Pass |
| 6. RED | `testGetRolesForTeamReturnsOnlyMatchingRoles` | Method doesn't exist | ❌ Fail |
| 7. GREEN | Same test | `filter()` + `map()` + team check | ✅ Pass |
| 8. RED | `testGetExpiredWandsReturnsOnlyExpired` | Method doesn't exist | ❌ Fail |
| 9. GREEN | Same test | `filter()` + `isExpired()` | ✅ Pass |

**Kodėl TDD su Collections?**
- **Type safety** - `Collection<UserRole>` vietoj `array`
- **Lazy loading** - neuzkrauna visų iš karto
- **Chainable** - `filter()` → `map()` → `toArray()`
- **Testable** - kiekvienas metodas turi atskirą testą
- **Maintainable** - refaktoringas be breakage

### 13.9 Intensive Collection Tests

**161 tests, 262 assertions** across 6 dedicated test files:

| Test File | Tests | Assertions | Coverage |
|-----------|-------|------------|----------|
| `UserRolesCollectionTest.php` | 12 | 30 | User roles, wands, patronuses, cloaks |
| `DoctrineCollectionAdvancedTest.php` | 24 | 41 | slice, partition, forAll, matching, isEmpty |
| `TeamCollectionTest.php` | 23 | 36 | users, roles, cloaks, countUsers |
| `RoleCollectionTest.php` | 16 | 26 | userRoles, wands, patronuses, permissions |
| `WandCollectionTest.php` | 20 | 34 | permissions, expiration, CRUD |
| `PatronusCollectionTest.php` | 18 | 28 | tokens, expiration, uniqueness |
| `InvisibilityCloakCollectionTest.php` | 20 | 25 | active/inactive, expiration, CRUD |

#### 13.9.1 Doctrine Collection API Coverage

| API Method | Test File | Tests |
|------------|-----------|-------|
| `filter()` | DoctrineCollectionAdvanced, RoleCollection | 4 |
| `map()` | DoctrineCollectionAdvanced, RoleCollection | 3 |
| `slice()` | DoctrineCollectionAdvanced, TeamCollection | 3 |
| `partition()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 3 |
| `first()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 4 |
| `forAll()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 3 |
| `matching()` | DoctrineCollectionAdvanced | 2 |
| `count()` | UserRolesCollection, TeamCollection | 3 |
| `isEmpty()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 4 |
| `contains()` | DoctrineCollectionAdvanced, TeamCollection | 3 |
| `add()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 4 |
| `remove()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 4 |
| `clear()` | DoctrineCollectionAdvanced, TeamCollection, RoleCollection | 3 |
| `get()` | DoctrineCollectionAdvanced | 1 |
| `set()` | DoctrineCollectionAdvanced | 1 |
| `getKeys()` | DoctrineCollectionAdvanced, TeamCollection | 2 |
| `getValues()` | DoctrineCollectionAdvanced, TeamCollection | 2 |
| `exists()` | UserRolesCollection | 1 |
| `getIterator()` | DoctrineCollectionAdvanced, TeamCollection | 2 |

#### 13.9.2 Entity Property Tests

| Entity | Property Tests | Coverage |
|--------|---------------|----------|
| **Wand** | name, permissions, createdAt, expiresAt, isExpired, hasPermission | 20 tests |
| **Patronus** | token, issuedAt, expiresAt, isValid, isExpired, uniqueness | 18 tests |
| **InvisibilityCloak** | grantedAt, expiresAt, isActive, activate, deactivate | 20 tests |
| **Role** | name, description, team, userRoles, wands, patronuses | 16 tests |
| **Team** | name, description, users, roles, invisibilityCloaks | 23 tests |
| **User** | email, password, createdAt, userRoles, wands, patronuses, cloaks | 12 tests |

---

## 15. Summary

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
│   └── Fixture/       # League Factory Muffin
├── schema/            # Doctrine XML mappings
│   ├── User.orm.xml
│   ├── Role.orm.xml
│   ├── UserRole.orm.xml
│   ├── Team.orm.xml
│   ├── Wand.orm.xml
│   ├── Patronus.orm.xml
│   ├── InvisibilityCloak.orm.xml
│   ├── Post.orm.xml
│   └── Group.orm.xml
├── templates/         # MVC PHP templates
│   ├── home.php
│   ├── users/
│   └── error/
└── tests/
    ├── Action/        # ADR Action Tests
    ├── Unit/          # Unit Tests
    │   ├── UserRolesCollectionTest.php
    │   ├── DoctrineCollectionAdvancedTest.php
    │   ├── TeamCollectionTest.php
    │   ├── RoleCollectionTest.php
    │   ├── WandCollectionTest.php
    │   ├── PatronusCollectionTest.php
    │   └── InvisibilityCloakCollectionTest.php
    └── factories/     # Factory Definitions
```

---

*"The best architecture is the one that fits your needs."* — Unknown
