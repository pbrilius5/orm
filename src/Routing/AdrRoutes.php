<?php

declare(strict_types=1);

namespace App\Routing;

use App\Action\User\ListAction;
use App\Action\User\ShowAction;
use App\Action\User\CreateAction;
use App\Action\User\UpdateAction;
use App\Action\User\PatchAction;
use App\Action\User\DeleteAction;
use App\Action\Group\ListAction as GroupListAction;
use App\Action\Group\ShowAction as GroupShowAction;
use App\Action\Group\CreateAction as GroupCreateAction;
use App\Action\Group\UpdateAction as GroupUpdateAction;
use App\Action\Group\PatchAction as GroupPatchAction;
use App\Action\Group\DeleteAction as GroupDeleteAction;
use Psr\Container\ContainerInterface;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AdrRoutes
{
    private Router $router;

    public function __construct(ContainerInterface $container)
    {
        $this->router = new Router();
        $strategy = new JsonStrategy(new ResponseFactory());
        $strategy->setContainer($container);
        $this->router->setStrategy($strategy);
        $this->register();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    private function register(): void
    {
        $this->router->map('GET', '/health', function (ServerRequestInterface $request): ResponseInterface {
            return new JsonResponse([
                '_links' => [
                    'self' => ['href' => '/health'],
                ],
                'status' => 'ok',
                'timestamp' => date('c'),
            ]);
        });

        $this->router->map('GET', '/api/users', [ListAction::class, '__invoke']);
        $this->router->map('POST', '/api/users', [CreateAction::class, '__invoke']);
        $this->router->map('GET', '/api/users/{id}', [ShowAction::class, '__invoke']);
        $this->router->map('PUT', '/api/users/{id}', [UpdateAction::class, '__invoke']);
        $this->router->map('PATCH', '/api/users/{id}', [PatchAction::class, '__invoke']);
        $this->router->map('DELETE', '/api/users/{id}', [DeleteAction::class, '__invoke']);

        $this->router->map('GET', '/api/groups', [GroupListAction::class, '__invoke']);
        $this->router->map('POST', '/api/groups', [GroupCreateAction::class, '__invoke']);
        $this->router->map('GET', '/api/groups/{id}', [GroupShowAction::class, '__invoke']);
        $this->router->map('PUT', '/api/groups/{id}', [GroupUpdateAction::class, '__invoke']);
        $this->router->map('PATCH', '/api/groups/{id}', [GroupPatchAction::class, '__invoke']);
        $this->router->map('DELETE', '/api/groups/{id}', [GroupDeleteAction::class, '__invoke']);

        $this->router->map('GET', '/manifest.json', function (ServerRequestInterface $request): ResponseInterface {
            return new JsonResponse([
                'name' => 'Oryx ORM App',
                'short_name' => 'OryxApp',
                'description' => 'Full-stack ORM with ADR pattern',
                'start_url' => '/',
                'display' => 'standalone',
                'background_color' => '#ffffff',
                'theme_color' => '#4A90E2',
                'icons' => [
                    ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                    ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ],
            ]);
        });
    }
}
