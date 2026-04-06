<?php

declare(strict_types=1);

namespace App\Container;

use App\Cache\CacheUnion;
use App\Controller\GroupController;
use App\Controller\UserController;
use App\Db;
use App\Dto\DtoFactory;
use App\Http\Router;
use App\View\ViewRenderer;
use App\Logger\LoggerFactory;
use App\Logger\CrashLogger;
use App\Event\ORMEventListener;
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
use App\Service\PersistentSingletonRegistry;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Laminas\ServiceManager\ServiceManager;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\Mapping\MapByStaticList;
use Oryx\ORM\EntityManager;
use Oryx\ORM\EntityManagerFactory;
use Psr\Log\LoggerInterface;

class MvcServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        EntityManager::class,
        Db::class,
        CacheUnion::class,
        FilesystemOperator::class,
        Router::class,
        ViewRenderer::class,
        UserController::class,
        GroupController::class,
        ServiceManager::class,
        LoggerInterface::class,
        CommandBus::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(FilesystemOperator::class, function () {
            $storagePath = getenv('FLYSYSTEM_STORAGE_PATH') ?: dirname(__DIR__, 2) . '/var/storage';
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0o755, true);
            }
            $adapter = new LocalFilesystemAdapter($storagePath);
            return new Filesystem($adapter);
        });

        $container->addShared(Db::class, function () use ($container) {
            $fs = $container->get(FilesystemOperator::class);
            Db::setFilesystem($fs);
            return Db::getInstance();
        });

        $container->addShared(CacheUnion::class, function () use ($container): CacheUnion {
            $fs = $container->get(FilesystemOperator::class);
            $entityManager = $container->get(EntityManager::class);
            $registry = new PersistentSingletonRegistry(
                $entityManager->getDoctrineEntityManager(),
                $container->get(LoggerInterface::class)
            );
            return CacheUnion::getInstance($fs, $registry);
        });

        $container->addShared(EntityManager::class, function () use ($container): EntityManager {
            $logger = $container->get(LoggerInterface::class);
            $listener = new ORMEventListener($logger);
            return EntityManagerFactory::getInstance($listener);
        });

        $container->addShared(LoggerInterface::class, function () {
            $appEnv = $_ENV['APP_ENV'] ?? 'dev';
            return LoggerFactory::create($appEnv);
        });

        $container->addShared(CrashLogger::class);

        $container->add(CommandBus::class, function () use ($container) {
            $mapping = new MapByStaticList([
                \App\Command\User\CreateUserCommand::class => [CreateUserHandler::class, 'handle'],
                \App\Command\User\UpdateUserCommand::class => [UpdateUserHandler::class, 'handle'],
                \App\Command\User\PatchUserCommand::class => [PatchUserHandler::class, 'handle'],
                \App\Command\User\DeleteUserCommand::class => [DeleteUserHandler::class, 'handle'],
                \App\Command\User\GetUserCommand::class => [GetUserHandler::class, 'handle'],
                \App\Command\User\ListUsersCommand::class => [ListUsersHandler::class, 'handle'],
                \App\Command\Group\CreateGroupCommand::class => [CreateGroupHandler::class, 'handle'],
                \App\Command\Group\UpdateGroupCommand::class => [UpdateGroupHandler::class, 'handle'],
                \App\Command\Group\PatchGroupCommand::class => [PatchGroupHandler::class, 'handle'],
                \App\Command\Group\DeleteGroupCommand::class => [DeleteGroupHandler::class, 'handle'],
                \App\Command\Group\GetGroupCommand::class => [GetGroupHandler::class, 'handle'],
                \App\Command\Group\ListGroupsCommand::class => [ListGroupsHandler::class, 'handle'],
                \App\Command\Console\LoadFixturesCommand::class => [LoadFixturesHandler::class, 'handle'],
                \App\Command\Console\CreateDatabaseCommand::class => [CreateDatabaseHandler::class, 'handle'],
                \App\Command\Console\GenerateProxiesCommand::class => [GenerateProxiesHandler::class, 'handle'],
                \App\Command\Console\ManageUserCommand::class => [ManageUserHandler::class, 'handle'],
            ]);

            $middleware = new CommandHandlerMiddleware($container, $mapping);

            return new CommandBus($middleware);
        });

        $container->add(Router::class);
        $container->add(ViewRenderer::class);

        $container->add(UserController::class)->addArgument(EntityManager::class)->addArgument(CommandBus::class)->addArgument(DtoFactory::class);
        $container->add(GroupController::class)->addArgument(EntityManager::class)->addArgument(CommandBus::class)->addArgument(DtoFactory::class);

        $container->addShared(ServiceManager::class, function (): ServiceManager {
            return LaminasServiceManagerFactory::create();
        });
    }
}
