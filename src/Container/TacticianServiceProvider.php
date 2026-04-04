<?php

declare(strict_types=1);

namespace App\Container;

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
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Doctrine\ORM\Middleware\DoctrineOrmMiddleware;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class TacticianServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        CommandBus::class,
    ];

    private array $commandToHandlerMap = [
        CreateUserCommand::class => CreateUserHandler::class,
        UpdateUserCommand::class => UpdateUserHandler::class,
        PatchUserCommand::class => PatchUserHandler::class,
        DeleteUserCommand::class => DeleteUserHandler::class,
        GetUserCommand::class => GetUserHandler::class,
        ListUsersCommand::class => ListUsersHandler::class,
        CreateGroupCommand::class => CreateGroupHandler::class,
        UpdateGroupCommand::class => UpdateGroupHandler::class,
        PatchGroupCommand::class => PatchGroupHandler::class,
        DeleteGroupCommand::class => DeleteGroupHandler::class,
        GetGroupCommand::class => GetGroupHandler::class,
        ListGroupsCommand::class => ListGroupsHandler::class,
        LoadFixturesCommand::class => LoadFixturesHandler::class,
        CreateDatabaseCommand::class => CreateDatabaseHandler::class,
        GenerateProxiesCommand::class => GenerateProxiesHandler::class,
        ManageUserCommand::class => ManageUserHandler::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(EntityManager::class);

        $this->registerHandlers($container);

        $commandHandlerMiddleware = new CommandHandlerMiddleware(
            new HandleClassNameInflector(),
            $container,
            $this->commandToHandlerMap
        );

        $commandBus = new CommandBus([$commandHandlerMiddleware]);

        $container->addShared(CommandBus::class, $commandBus);
    }

    private function registerHandlers($container): void
    {
        foreach ($this->commandToHandlerMap as $handlerClass) {
            $container->add($handlerClass)->autowire();
        }
    }
}
