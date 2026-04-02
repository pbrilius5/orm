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
13. [Summary](#13-summary)

---

## 1. Quick Start (SQLite, 30 seconds)

No database server needed. SQLite is the default driver.

```bash
# 1. Install dependencies
composer install

# 2. Copy environment configuration
cp .env.dist .env

# 3. Create database and schema from XML
bin/console oryx:db:create

# 4. Load demo fixtures (groups, users, posts)
bin/console oryx:fixtures:load

# 5. Start the server
composer serve
```

Open [http://localhost:8080](http://localhost:8080) — you should see the home page with users and API links.

### What you get

| URL | What |
|-----|------|
| [http://localhost:8080/](http://localhost:8080/) | MVC home page (PHP templates) |
| [http://localhost:8080/users](http://localhost:8080/users) | User list with CRUD |
| [http://localhost:8080/api/users](http://localhost:8080/api/users) | HAL+JSON API collection |
| [http://localhost:8080/api/users/1](http://localhost:8080/api/users/1) | Single user resource |
| [http://localhost:8080/api/users?include=posts,group](http://localhost:8080/api/users?include=posts,group) | With embedded relations |

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

### 7.6 Užklausų pavyzdžiai

```bash
# All users
curl -X GET http://localhost:8000/api/users

# Users with posts
curl -X GET "http://localhost:8000/api/users?include=posts"

# Users with all relations
curl -X GET "http://localhost:8000/api/users?include=posts,group"

# Create user
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"email":"new@versliukai.lt","password":"secret123"}'

# Full update
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"email":"updated@versliukai.lt","roles":["ROLE_ADMIN"]}'

# Partial update
curl -X PATCH http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"email":"patched@versliukai.lt"}'

# Delete
curl -X DELETE http://localhost:8000/api/users/1
```

### 7.7 Maršrutų struktūra

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
    protected $availableIncludes = ['posts', 'group'];

    public function transform(User $user): array
    {
        return [
            'id' => $user->getId() ?? 0,
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
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

$fm->define(User::class)->setDefinitions([
    'email' => 'user{++}@example.com',
    'password' => 'password123',
    'roles' => ['ROLE_USER'],
    'createdAt' => fn() => new \DateTimeImmutable(),
])->setCallback(function (User $user) {
    $user->setGroup(null);
});
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

## 11. Environment Configuration

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

## 12. XML Schema-Driven Entity Generation

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
        <field name="roles" type="json"/>
        <field name="createdAt" type="datetime"/>
        <field name="updatedAt" type="datetime" nullable="true"/>
        <one-to-many target-entity="App\Entity\Post" field="posts" mapped-by="author"/>
        <many-to-one target-entity="App\Entity\Group" field="group" inversed-by="users"/>
    </entity>
</doctrine-mapping>
```

---

## Summary

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
│   ├── Post.orm.xml
│   └── Group.orm.xml
├── templates/         # MVC PHP templates
│   ├── home.php
│   ├── users/
│   └── error/
└── tests/
    ├── Action/        # ADR Action Tests
    ├── Unit/          # Unit Tests
    └── factories/     # Factory Definitions
```

---

*"The best architecture is the one that fits your needs."* — Unknown
