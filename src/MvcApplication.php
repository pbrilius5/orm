<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\View\ViewRenderer;
use Doctrine\ORM\EntityManager;

/**
 * MVC Application - Vanilla PHP, no laminas/diactoros.
 *
 * Uses:
 * - App\Http\Request (vanilla)
 * - App\Http\Response (vanilla)
 * - App\Http\Router (vanilla)
 * - App\View\ViewRenderer (vanilla PHP templates)
 * - Doctrine ORM for data
 */
class MvcApplication
{
    private Router $router;
    private ViewRenderer $view;
    private EntityManager $em;
    private array $controllers = [];

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->router = new Router();
        $this->view = new ViewRenderer();
        $this->registerControllers();
        $this->registerRoutes();
    }

    private function registerControllers(): void
    {
        $this->controllers['user'] = new \App\Controller\UserController($this->em);
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', function (Request $req) {
            return new Response($this->view->renderWithLayout('home', [
                'title' => 'Oryx ORM - MVC Application',
                'description' => 'Full-stack ORM with MVC pattern',
                'breadcrumbs' => [['label' => 'Home', 'url' => '/']],
            ]));
        });

        $this->router->get('/users', function (Request $req) {
            $data = $this->controllers['user']->index();
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Users', 'url' => '/users'],
            ];
            return new Response($this->view->renderWithLayout('users/index', $data));
        });

        $this->router->get('/users/create', function (Request $req) {
            return new Response($this->view->renderWithLayout('users/create', [
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => 'Create', 'url' => '/users/create'],
                ],
            ]));
        });

        $this->router->post('/users/create', function (Request $req) {
            $this->controllers['user']->create($req->all());
            header('Location: /users');
            exit;
        });

        $this->router->get('/users/{id}', function (Request $req, array $params) {
            $user = $this->controllers['user']->show((int) $params['id']);
            if (!$user) {
                return new Response('User not found', 404);
            }
            return new Response($this->view->renderWithLayout('users/show', [
                'user' => $user,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => '#' . $user->getId(), 'url' => '/users/' . $user->getId()],
                ],
            ]));
        });

        $this->router->get('/users/{id}/delete', function (Request $req, array $params) {
            $this->controllers['user']->delete((int) $params['id']);
            header('Location: /users');
            exit;
        });

        $this->router->get('/users/{id}/edit', function (Request $req, array $params) {
            $user = $this->controllers['user']->show((int) $params['id']);
            if (!$user) {
                return new Response('User not found', 404);
            }
            return new Response($this->view->renderWithLayout('users/edit', [
                'user' => $user,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => '#' . $user->getId(), 'url' => '/users/' . $user->getId()],
                    ['label' => 'Edit', 'url' => '/users/' . $user->getId() . '/edit'],
                ],
            ]));
        });

        $this->router->post('/users/{id}/edit', function (Request $req, array $params) {
            $user = $this->controllers['user']->update((int) $params['id'], $req->all());
            if (!$user) {
                return new Response('User not found', 404);
            }
            header('Location: /users');
            exit;
        });
    }

    public function run(): void
    {
        $request = new Request();
        $response = $this->router->dispatch($request);

        if ($response) {
            $response->send();
        } else {
            http_response_code(404);
            echo $this->view->render('error/404', ['message' => 'Page not found']);
        }
    }
}
