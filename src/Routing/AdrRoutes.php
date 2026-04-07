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
    private ContainerInterface $container;
    private ResponseFactory $responseFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->responseFactory = new ResponseFactory();
        $this->router = new Router();
        $strategy = new JsonStrategy($this->responseFactory);
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
        $this->router->map('GET', '/api/health', function (ServerRequestInterface $request): ResponseInterface {
            return new JsonResponse([
                '_links' => [
                    'self' => ['href' => '/api/health'],
                ],
                'status' => 'ok',
                'timestamp' => date('c'),
            ]);
        });

        $this->router->map('GET', '/api/users', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(ListAction::class, $request);
        });
        $this->router->map('POST', '/api/users', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(CreateAction::class, $request);
        });
        $this->router->map('GET', '/api/users/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(ShowAction::class, $request, $routeVars);
        });
        $this->router->map('PUT', '/api/users/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(UpdateAction::class, $request, $routeVars);
        });
        $this->router->map('PATCH', '/api/users/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(PatchAction::class, $request, $routeVars);
        });
        $this->router->map('DELETE', '/api/users/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(DeleteAction::class, $request, $routeVars);
        });

        $this->router->map('GET', '/api/groups', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(GroupListAction::class, $request);
        });
        $this->router->map('POST', '/api/groups', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(GroupCreateAction::class, $request);
        });
        $this->router->map('GET', '/api/groups/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(GroupShowAction::class, $request, $routeVars);
        });
        $this->router->map('PUT', '/api/groups/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(GroupUpdateAction::class, $request, $routeVars);
        });
        $this->router->map('PATCH', '/api/groups/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(GroupPatchAction::class, $request, $routeVars);
        });
        $this->router->map('DELETE', '/api/groups/{id}', function (ServerRequestInterface $request, array $routeVars): ResponseInterface {
            return $this->resolveAction(GroupDeleteAction::class, $request, $routeVars);
        });

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

    private function resolveAction(string $actionClass, ServerRequestInterface $request, array $routeVars = []): ResponseInterface
    {
        $action = $this->container->get($actionClass);
        $response = $this->responseFactory->createResponse();

        foreach ($routeVars as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $action($request, $response);
    }
}
