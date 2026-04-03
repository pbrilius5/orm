<?php

declare(strict_types=1);

namespace App\Container;

use App\Controller\GroupController;
use App\Controller\UserController;
use App\Http\Router;
use App\View\ViewRenderer;
use Laminas\ServiceManager\ServiceManager;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Oryx\ORM\EntityManager;
use Oryx\ORM\EntityManagerFactory;

class MvcServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        EntityManager::class,
        Router::class,
        ViewRenderer::class,
        UserController::class,
        GroupController::class,
        ServiceManager::class,
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

        $container->add(Router::class);
        $container->add(ViewRenderer::class);

        $container->add(UserController::class)->addArgument(EntityManager::class);
        $container->add(GroupController::class)->addArgument(EntityManager::class);

        $container->addShared(ServiceManager::class, function (): ServiceManager {
            return LaminasServiceManagerFactory::create();
        });
    }
}
