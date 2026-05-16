<?php

declare(strict_types=1);

namespace App\Routing;

use App\Controller\UserController;
use App\Dto\DtoFactory;
use Prototype\Mvc\Application;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;
use App\View\ViewRenderer;
use Oryx\ORM\EntityManager;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;
use Laminas\ServiceManager\ServiceManager;
use DI\Container as PhpDiContainer;
use App\View\Helper\FormHelper;
use App\Form\UserForm;
use App\Form\GroupForm;
use App\Service\WorkGroupMapInterface;
use App\Controller\GroupController;

class MvcRoutes
{
    private Application $router;
    private array $controllers;
    private ViewRenderer $view;
    private ServiceManager $laminasSm;
    private PhpDiContainer $phpDiContainer;

    public function __construct(
        EntityManager $em,
        CommandBus $commandBus,
        DtoFactory $dtoFactory,
        LoggerInterface $logger,
        ViewRenderer $view,
        ServiceManager $laminasSm,
        PhpDiContainer $phpDiContainer
    ) {
        $this->view = $view;
        $this->router = new Application();
        $this->controllers = [
            'user' => new UserController($em, $commandBus, $dtoFactory, $logger),
            'group' => new GroupController($em, $commandBus, $dtoFactory, $logger),
        ];
        $this->laminasSm = $laminasSm;
        $this->phpDiContainer = $phpDiContainer;
        $this->register();
    }

    public function getRouter(): \League\Route\Router
    {
        return $this->router->getRouter();
    }

    private function register(): void
    {
        $this->router->get('/', function (ServerRequestInterface $req) {
            return new Response(200, [], $this->view->renderWithLayout('home', [
                'title' => 'Oryx ORM - MVC Application',
                'description' => 'Full-stack ORM with MVC pattern',
                'breadcrumbs' => [['label' => 'Home', 'url' => '/']],
            ]));
        });

        $this->router->get('/users', function (ServerRequestInterface $req) {
            $controller = $this->controllers['user'];
            $data = $controller->index();
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Users', 'url' => '/users'],
            ];
            return new Response(200, [], $this->view->renderWithLayout('users/index', $data));
        });

        $this->router->get('/users/create', function (ServerRequestInterface $req) {
            $controller = $this->controllers['user'];
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $form->setDefaultGamificationRole(\App\Entity\WizardRole::NAME);
            $form->setAttribute('action', '/users/create');
            return new Response(200, [], $this->view->renderWithLayout('users/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => 'Create', 'url' => '/users/create'],
                ],
            ]));
        });

        $this->router->post('/users/create', function (ServerRequestInterface $req) {
            $controller = $this->controllers['user'];
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $form->setDefaultGamificationRole(\App\Entity\WizardRole::NAME);
            $data = array_merge((array) $req->getParsedBody(), $req->getQueryParams());
            $form->setData($data);

            if ($form->isValid()) {
                try {
                    $controller->create($data);
                    header('Location: /users');
                    exit;
                } catch (\InvalidArgumentException $e) {
                    $form->setMessages(['email' => [$e->getMessage()]]);
                }
            }

            $form->setData($data);
            return new Response(422, [], $this->view->renderWithLayout('users/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => 'Create', 'url' => '/users/create'],
                ],
            ]));
        });

        $this->router->get('/users/{id}', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['user'];
            $user = $controller->show($params['id']);
            if (!$user) {
                return new Response(404, [], 'User not found');
            }
            return new Response(200, [], $this->view->renderWithLayout('users/show', [
                'user' => $user,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Users', 'url' => '/users'],
                    ['label' => '#' . $user->getId(), 'url' => '/users/' . $user->getId()],
                ],
            ]));
        });

        $this->router->get('/users/{id}/delete', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['user'];
            $controller->delete($params['id']);
            header('Location: /users');
            exit;
        });

        $this->router->get('/users/{id}/edit', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['user'];
            $user = $controller->show($params['id']);
            if (!$user) {
                return new Response(404, [], 'User not found');
            }
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $form->setAttribute('action', '/users/' . $user->getId() . '/edit');
            $form->get('email')->setValue($user->getEmail());
            $form->setGamificationRoles($user->getGamificationRoleNames());
            $workGroup = $user->getWorkGroup();
            if ($workGroup) {
                $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
                $discriminator = $workGroupMap->getDiscriminatorForGroup($workGroup);
                if ($discriminator !== 'group') {
                    $form->get('work_group')->setValue($discriminator);
                }
            }
            return new Response(200, [], $this->view->renderWithLayout('users/edit', [
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

        $this->router->post('/users/{id}/edit', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['user'];
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $data = array_merge((array) $req->getParsedBody(), $req->getQueryParams());
            $form->setData($data);

            if ($form->isValid()) {
                $controller->update($params['id'], $data);
                if (!$user = $controller->show($params['id'])) {
                    return new Response(404, [], 'User not found');
                }
                header('Location: /users');
                exit;
            }

            $user = $controller->show($params['id']);
            return new Response(422, [], $this->view->renderWithLayout('users/edit', [
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

        $this->router->get('/groups', function (ServerRequestInterface $req) {
            $controller = $this->controllers['group'];
            $search = $req->getQueryParams()['search'] ?? null;
            $data = $controller->index($search);
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Groups', 'url' => '/groups'],
            ];
            return new Response(200, [], $this->view->renderWithLayout('groups/index', $data));
        });

        $this->router->get('/groups/create', function (ServerRequestInterface $req) {
            $form = new GroupForm(null, [], $this->laminasSm);
            $form->setAttribute('action', '/groups/create');
            return new Response(200, [], $this->view->renderWithLayout('groups/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => 'Create', 'url' => '/groups/create'],
                ],
            ]));
        });

        $this->router->post('/groups/create', function (ServerRequestInterface $req) {
            $form = new GroupForm(null, [], $this->laminasSm);
            $data = array_merge((array) $req->getParsedBody(), $req->getQueryParams());
            $form->setData($data);

            if ($form->isValid()) {
                $controller = $this->controllers['group'];
                $controller->create($data);
                header('Location: /groups');
                exit;
            }

            return new Response(422, [], $this->view->renderWithLayout('groups/create', [
                'form' => $form,
                'formHelper' => FormHelper::class,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => 'Create', 'url' => '/groups/create'],
                ],
            ]));
        });

        $this->router->get('/groups/{id}', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['group'];
            $group = $controller->show((int) $params['id']);
            if (!$group) {
                return new Response(404, [], 'Group not found');
            }
            return new Response(200, [], $this->view->renderWithLayout('groups/show', [
                'group' => $group,
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Groups', 'url' => '/groups'],
                    ['label' => '#' . $group->getId(), 'url' => '/groups/' . $group->getId()],
                ],
            ]));
        });

        $this->router->get('/groups/{id}/delete', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['group'];
            $controller->delete((int) $params['id']);
            header('Location: /groups');
            exit;
        });

        $this->router->get('/groups/{id}/edit', function (ServerRequestInterface $req, array $params) {
            $controller = $this->controllers['group'];
            $group = $controller->show((int) $params['id']);
            if (!$group) {
                return new Response(404, [], 'Group not found');
            }
            $form = new GroupForm(null, [], $this->laminasSm);
            $form->setAttribute('action', '/groups/' . $group->getId() . '/edit');
            return new Response(200, [], $this->view->renderWithLayout('groups/edit', [
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

        $this->router->post('/groups/{id}/edit', function (ServerRequestInterface $req, array $params) {
            $form = new GroupForm(null, [], $this->laminasSm);
            $data = array_merge((array) $req->getParsedBody(), $req->getQueryParams());
            $form->setData($data);

            if ($form->isValid()) {
                $controller = $this->controllers['group'];
                $group = $controller->update((int) $params['id'], $data);
                if (!$group) {
                    return new Response(404, [], 'Group not found');
                }
                header('Location: /groups');
                exit;
            }

            $controller = $this->controllers['group'];
            $group = $controller->show((int) $params['id']);
            return new Response(422, [], $this->view->renderWithLayout('groups/edit', [
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
    }
}
