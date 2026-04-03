<?php

declare(strict_types=1);

namespace App;

use App\Form\UserForm;
use App\Form\GroupForm;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\View\ViewRenderer;
use App\View\Helper\FormHelper;
use Doctrine\ORM\EntityManager;

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
        $this->controllers['group'] = new \App\Controller\GroupController($this->em);
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

        $this->registerUserRoutes();
        $this->registerGroupRoutes();
    }

    private function registerUserRoutes(): void
    {
        $this->router->get('/users', function (Request $req) {
            $data = $this->controllers['user']->index();
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Users', 'url' => '/users'],
            ];
            return new Response($this->view->renderWithLayout('users/index', $data));
        });

        $this->router->get('/users/create', function (Request $req) {
            $form = new UserForm();
            $form->setAttribute('action', '/users/create');
            return new Response($this->view->renderWithLayout('users/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => 'Create', 'url' => '/users/create'],
                ],
            ]));
        });

        $this->router->post('/users/create', function (Request $req) {
            $form = new UserForm();
            $form->setData($req->all());

            if ($form->isValid()) {
                $this->controllers['user']->create($req->all());
                header('Location: /users');
                exit;
            }

            $form->setData($req->all());
            return new Response($this->view->renderWithLayout('users/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => 'Create', 'url' => '/users/create'],
                ],
            ]), 422);
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
            $form = new UserForm();
            $form->setAttribute('action', '/users/' . $user->getId() . '/edit');
            return new Response($this->view->renderWithLayout('users/edit', [
                'user' => $user,
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => '#' . $user->getId(), 'url' => '/users/' . $user->getId()],
                    ['label' => 'Edit', 'url' => '/users/' . $user->getId() . '/edit'],
                ],
            ]));
        });

        $this->router->post('/users/{id}/edit', function (Request $req, array $params) {
            $form = new UserForm();
            $form->setData($req->all());

            if ($form->isValid()) {
                $user = $this->controllers['user']->update((int) $params['id'], $req->all());
                if (!$user) {
                    return new Response('User not found', 404);
                }
                header('Location: /users');
                exit;
            }

            $user = $this->controllers['user']->show((int) $params['id']);
            return new Response($this->view->renderWithLayout('users/edit', [
                'user' => $user,
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => '#' . $user->getId(), 'url' => '/users/' . $user->getId()],
                    ['label' => 'Edit', 'url' => '/users/' . $user->getId() . '/edit'],
                ],
            ]), 422);
        });
    }

    private function registerGroupRoutes(): void
    {
        $this->router->get('/groups', function (Request $req) {
            $data = $this->controllers['group']->index();
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Groups', 'url' => '/groups'],
            ];
            return new Response($this->view->renderWithLayout('groups/index', $data));
        });

        $this->router->get('/groups/create', function (Request $req) {
            $form = new GroupForm();
            $form->setAttribute('action', '/groups/create');
            return new Response($this->view->renderWithLayout('groups/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => 'Create', 'url' => '/groups/create'],
                ],
            ]));
        });

        $this->router->post('/groups/create', function (Request $req) {
            $form = new GroupForm();
            $form->setData($req->all());

            if ($form->isValid()) {
                $this->controllers['group']->create($req->all());
                header('Location: /groups');
                exit;
            }

            return new Response($this->view->renderWithLayout('groups/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => 'Create', 'url' => '/groups/create'],
                ],
            ]), 422);
        });

        $this->router->get('/groups/{id}', function (Request $req, array $params) {
            $group = $this->controllers['group']->show((int) $params['id']);
            if (!$group) {
                return new Response('Group not found', 404);
            }
            return new Response($this->view->renderWithLayout('groups/show', [
                'group' => $group,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => '#' . $group->getId(), 'url' => '/groups/' . $group->getId()],
                ],
            ]));
        });

        $this->router->get('/groups/{id}/delete', function (Request $req, array $params) {
            $this->controllers['group']->delete((int) $params['id']);
            header('Location: /groups');
            exit;
        });

        $this->router->get('/groups/{id}/edit', function (Request $req, array $params) {
            $group = $this->controllers['group']->show((int) $params['id']);
            if (!$group) {
                return new Response('Group not found', 404);
            }
            $form = new GroupForm();
            $form->setAttribute('action', '/groups/' . $group->getId() . '/edit');
            return new Response($this->view->renderWithLayout('groups/edit', [
                'group' => $group,
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => '#' . $group->getId(), 'url' => '/groups/' . $group->getId()],
                    ['label' => 'Edit', 'url' => '/groups/' . $group->getId() . '/edit'],
                ],
            ]));
        });

        $this->router->post('/groups/{id}/edit', function (Request $req, array $params) {
            $form = new GroupForm();
            $form->setData($req->all());

            if ($form->isValid()) {
                $group = $this->controllers['group']->update((int) $params['id'], $req->all());
                if (!$group) {
                    return new Response('Group not found', 404);
                }
                header('Location: /groups');
                exit;
            }

            $group = $this->controllers['group']->show((int) $params['id']);
            return new Response($this->view->renderWithLayout('groups/edit', [
                'group' => $group,
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => '#' . $group->getId(), 'url' => '/groups/' . $group->getId()],
                    ['label' => 'Edit', 'url' => '/groups/' . $group->getId() . '/edit'],
                ],
            ]), 422);
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
