<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use DI\ContainerBuilder;
use DI\Container;
use Symfony\Component\Dotenv\Dotenv;
use Oryx\ORM\EntityManager;
use App\Routing\AdrRoutes;
use App\Middleware\SecurityMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Logger\CrashLogger;
use App\Logger\LoggerFactory;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Serializer\JsonApiSerializer;
use App\Command\User\CreateUserCommand;
use App\Command\User\UpdateUserCommand;
use App\Command\User\PatchUserCommand;
use App\Command\User\DeleteUserCommand;
use App\Command\User\GetUserCommand;
use App\Command\User\ListUsersCommand;
use App\Command\Group\CreateGroupCommand;
use App\Command\Group\UpdateGroupCommand;
use App\Command\Group\PatchGroupCommand;
use App\Command\Group\DeleteGroupCommand;
use App\Command\Group\GetGroupCommand;
use App\Command\Group\ListGroupsCommand;
use App\Command\Console\LoadFixturesCommand;
use App\Command\Console\CreateDatabaseCommand;
use App\Command\Console\GenerateProxiesCommand;
use App\Command\Console\ManageUserCommand;
use App\Handler\User\CreateUserHandler;
use App\Handler\User\UpdateUserHandler;
use App\Handler\User\PatchUserHandler;
use App\Handler\User\DeleteUserHandler;
use App\Handler\User\GetUserHandler;
use App\Handler\User\ListUsersHandler;
use App\Handler\Group\CreateGroupHandler;
use App\Handler\Group\UpdateGroupHandler;
use App\Handler\Group\PatchGroupHandler;
use App\Handler\Group\DeleteGroupHandler;
use App\Handler\Group\GetGroupHandler;
use App\Handler\Group\ListGroupsHandler;
use App\Handler\Console\LoadFixturesHandler;
use App\Handler\Console\CreateDatabaseHandler;
use App\Handler\Console\GenerateProxiesHandler;
use App\Handler\Console\ManageUserHandler;
use App\Command\CommandBusInterface;
use App\Command\TacticianCommandBus;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\Mapping\MapByStaticList;
use Oryx\ORM\EntityManagerFactory;
use App\Fixture\FixtureLoader;
use App\Dto\DtoFactory;

use function DI\autowire;

/**
 * ADR API Kernel - routes separated to App\Routing\AdrRoutes.
 *
 * Middleware Stack (in order):
 * 1. SecurityMiddleware - Headers
 * 2. CorsMiddleware - CORS
 * 3. RateLimitMiddleware - Rate limiting
 * 4. CsrfMiddleware - CSRF protection
 */
class Kernel
{
    private string $environment;
    private Container $container;
    private AdrRoutes $adrRoutes;
    private EntityManager $entityManager;
    private FractalManager $fractal;
    private ?CrashLogger $crashLogger = null;
    private ?LoggerInterface $logger = null;

    public function __construct(string $environment = 'dev')
    {
        $this->environment = $environment;

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);
        $this->container = $builder->build();

        $this->boot();
    }

    public function boot(): void
    {
        $this->loadEnvironment();
        $this->createServices();
        $this->registerServices();
        $this->initLogging();
        $this->registerRoutes();
    }

    private function initLogging(): void
    {
        $this->logger = LoggerFactory::create($this->environment);
        $this->crashLogger = new CrashLogger();
    }

    private function loadEnvironment(): void
    {
        $dotenv = new Dotenv();
        $dotenv->bootEnv(dirname(__DIR__) . '/.env');
    }

    private function createServices(): void
    {
        $this->entityManager = EntityManagerFactory::getInstance();
        $this->fractal = new FractalManager(null);
        $this->fractal->setSerializer(new JsonApiSerializer());
    }

    private function registerServices(): void
    {
        $this->container->set(EntityManager::class, $this->entityManager);
        $this->container->set(FractalManager::class, $this->fractal);
        $this->container->set(FixtureLoader::class, autowire());
        $this->container->set(DtoFactory::class, autowire());

        $this->registerCommandBus();
    }

    private function registerCommandBus(): void
    {
        $commandToHandlerMap = [
            CreateUserCommand::class => [CreateUserHandler::class, 'handle'],
            UpdateUserCommand::class => [UpdateUserHandler::class, 'handle'],
            PatchUserCommand::class => [PatchUserHandler::class, 'handle'],
            DeleteUserCommand::class => [DeleteUserHandler::class, 'handle'],
            GetUserCommand::class => [GetUserHandler::class, 'handle'],
            ListUsersCommand::class => [ListUsersHandler::class, 'handle'],
            CreateGroupCommand::class => [CreateGroupHandler::class, 'handle'],
            UpdateGroupCommand::class => [UpdateGroupHandler::class, 'handle'],
            PatchGroupCommand::class => [PatchGroupHandler::class, 'handle'],
            DeleteGroupCommand::class => [DeleteGroupHandler::class, 'handle'],
            GetGroupCommand::class => [GetGroupHandler::class, 'handle'],
            ListGroupsCommand::class => [ListGroupsHandler::class, 'handle'],
            LoadFixturesCommand::class => [LoadFixturesHandler::class, 'handle'],
            CreateDatabaseCommand::class => [CreateDatabaseHandler::class, 'handle'],
            GenerateProxiesCommand::class => [GenerateProxiesHandler::class, 'handle'],
            ManageUserCommand::class => [ManageUserHandler::class, 'handle'],
        ];

        foreach ($commandToHandlerMap as [$handlerClass]) {
            $this->container->set($handlerClass, autowire());
        }

        $mapping = new MapByStaticList($commandToHandlerMap);

        $commandHandlerMiddleware = new CommandHandlerMiddleware(
            $this->container,
            $mapping
        );

        $commandBus = new CommandBus($commandHandlerMiddleware);

        $this->container->set(CommandBus::class, $commandBus);
        $this->container->set(CommandBusInterface::class, autowire(TacticianCommandBus::class));
    }

    private function registerRoutes(): void
    {
        $this->adrRoutes = new AdrRoutes($this->container);
        $router = $this->adrRoutes->getRouter();

        $logger = $this->logger ?? LoggerFactory::create($this->environment);

        $router->middleware(new SecurityMiddleware($logger));
        $router->middleware(new CorsMiddleware($logger));
        $router->middleware(new RateLimitMiddleware($logger, 100, 60));
        $router->middleware(new CsrfMiddleware($logger));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger?->debug('Incoming request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $request->getHeaderLine('User-Agent') ?: 'unknown',
        ]);

        try {
            return $this->adrRoutes->getRouter()->dispatch($request);
        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }

    private function handleError(\Throwable $e): ResponseInterface
    {
        $isCrash = CrashLogger::isCrash($e);

        if ($isCrash) {
            $this->crashLogger?->critical('CRASH: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return JsonHalResponder::error('Service Unavailable', 503, 'A critical error occurred');
        }

        $this->logger?->error('Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());

        $debug = !in_array($this->environment, ['prod', 'production'], true);

        $error = [
            '_error' => [
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $debug ? $e->getMessage() : 'An error occurred',
            ],
        ];

        if ($debug) {
            $error['_error']['trace'] = $e->getTraceAsString();
        }

        return new JsonResponse($error, 500, [
            'Content-Type' => 'application/hal+json',
        ]);
    }

    public function terminate(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->close();
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDebug(): bool
    {
        return !in_array($this->environment, ['prod', 'production'], true);
    }
}
