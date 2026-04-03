<?php

declare(strict_types=1);

namespace App\Container;

use App\Controller\GroupController;
use App\Controller\UserController;
use App\Http\Router;
use App\View\ViewRenderer;
use App\Logger\LoggerFactory;
use App\Logger\CrashLogger;
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

        $container->addShared(EntityManager::class, function (): EntityManager {
            return EntityManagerFactory::getInstance();
        });

        $container->addShared(LoggerInterface::class, function () {
            $appEnv = $_ENV['APP_ENV'] ?? 'dev';
            return LoggerFactory::create($appEnv);
        });

        $container->addShared(CrashLogger::class);

        $container->add(CommandBus::class, function () use ($container) {
            $mapping = new MapByStaticList([
                \App\Command\User\CreateUserCommand::class => CreateUserHandler::class,
                \App\Command\User\UpdateUserCommand::class => UpdateUserHandler::class,
                \App\Command\User\PatchUserCommand::class => PatchUserHandler::class,
                \App\Command\User\DeleteUserCommand::class => DeleteUserHandler::class,
                \App\Command\User\GetUserCommand::class => GetUserHandler::class,
                \App\Command\User\ListUsersCommand::class => ListUsersHandler::class,
                \App\Command\Group\CreateGroupCommand::class => CreateGroupHandler::class,
                \App\Command\Group\UpdateGroupCommand::class => UpdateGroupHandler::class,
                \App\Command\Group\PatchGroupCommand::class => PatchGroupHandler::class,
                \App\Command\Group\DeleteGroupCommand::class => DeleteGroupHandler::class,
                \App\Command\Group\GetGroupCommand::class => GetGroupHandler::class,
                \App\Command\Group\ListGroupsCommand::class => ListGroupsHandler::class,
                \App\Command\Console\LoadFixturesCommand::class => LoadFixturesHandler::class,
                \App\Command\Console\CreateDatabaseCommand::class => CreateDatabaseHandler::class,
                \App\Command\Console\GenerateProxiesCommand::class => GenerateProxiesHandler::class,
                \App\Command\Console\ManageUserCommand::class => ManageUserHandler::class,
            ]);

            $middleware = new CommandHandlerMiddleware($mapping, $container);

            return new CommandBus([$middleware]);
        });

        $container->add(Router::class);
        $container->add(ViewRenderer::class);

        $container->add(UserController::class)->addArgument(EntityManager::class)->addArgument(CommandBus::class);
        $container->add(GroupController::class)->addArgument(EntityManager::class)->addArgument(CommandBus::class);

        $container->addShared(ServiceManager::class, function (): ServiceManager {
            return LaminasServiceManagerFactory::create();
        });

        $this->registerHandlers($container);
    }

    private function registerHandlers($container): void
    {
        $container->add(CreateUserHandler::class)->autowire();
        $container->add(UpdateUserHandler::class)->autowire();
        $container->add(PatchUserHandler::class)->autowire();
        $container->add(DeleteUserHandler::class)->autowire();
        $container->add(GetUserHandler::class)->autowire();
        $container->add(ListUsersHandler::class)->autowire();
        $container->add(CreateGroupHandler::class)->autowire();
        $container->add(UpdateGroupHandler::class)->autowire();
        $container->add(PatchGroupHandler::class)->autowire();
        $container->add(DeleteGroupHandler::class)->autowire();
        $container->add(GetGroupHandler::class)->autowire();
        $container->add(ListGroupsHandler::class)->autowire();
        $container->add(LoadFixturesHandler::class)->autowire();
        $container->add(CreateDatabaseHandler::class)->autowire();
        $container->add(GenerateProxiesHandler::class)->autowire();
        $container->add(ManageUserHandler::class)->autowire();
    }
}
