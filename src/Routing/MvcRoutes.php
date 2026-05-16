<?php

declare(strict_types=1);

namespace App\Routing;

use App\Controller\UserController;
use App\Dto\DtoFactory;
use Oryx\Mvc\Application as MvcApplicationRouter; // Use Oryx\Mvc\Application as the router
use Oryx\Mvc\Request;
use Oryx\Mvc\Response;
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
    private MvcApplicationRouter $router; // Updated property type
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
        $this->router = new MvcApplicationRouter($this->view); // Instantiated MvcApplicationRouter
        $this->controllers = [
            'user' => new UserController($em, $commandBus, $dtoFactory, $logger),
            'group' => new GroupController($em, $commandBus, $dtoFactory, $logger),
        ];
        $this->laminasSm = $laminasSm;
        $this->phpDiContainer = $phpDiContainer;
        $this->register();
    }

    public function getRouter(): MvcApplicationRouter // Updated return type
    {
        return $this->router;
    }

    private function register(): void
    {
        $this->router->get('/', function (Request $req) {
            return new Response($this->view->renderWithLayout('home', [
                'title' => 'Oryx ORM - MVC Application',
                'description' => 'Full-stack ORM with MVC pattern',
                'breadcrumbs' => [['label' => 'Home', 'url' => '/']],
            ]));
        });

        // User Routes
        $this->router->get('/users', function (Request $req) {
            $controller = $this->controllers['user'];
            $data = $controller->index();
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Users', 'url' => '/users'],
            ];
            return new Response($this->view->renderWithLayout('users/index', $data));
        });

        $this->router->get('/users/create', function (Request $req) {
            $controller = $this->controllers['user'];
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $form->setDefaultGamificationRole(\App\Entity\WizardRole::NAME);
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
            $controller = $this->controllers['user'];
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $form->setDefaultGamificationRole(\App\Entity\WizardRole::NAME);
            $form->setData($req->all());

            if ($form->isValid()) {
                try {
                    $controller->create($req->all());
                    header('Location: /users');
                    exit;
                } catch (\InvalidArgumentException $e) {
                    $form->setMessages(['email' => [$e->getMessage()]]);
                }
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
            $controller = $this->controllers['user'];
            $user = $controller->show($params['id']);
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
            $controller = $this->controllers['user'];
            $controller->delete($params['id']);
            header('Location: /users');
            exit;
        });

        $this->router->get('/users/{id}/edit', function (Request $req, array $params) {
            $controller = $this->controllers['user'];
            $user = $controller->show($params['id']);
            if (!$user) {
                return new Response('User not found', 404);
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
            $controller = $this->controllers['user'];
            $workGroupMap = $this->phpDiContainer->get(WorkGroupMapInterface::class);
            $form = new UserForm(null, [], $this->laminasSm, $workGroupMap);
            $form->setWorkGroups($controller->getWorkGroups());
            $form->setData($req->all());

            if ($form->isValid()) {
                $controller->update($params['id'], $req->all());
                if (!$user = $controller->show($params['id'])) {
                    return new Response('User not found', 404);
                }
                header('Location: /users');
                exit;
            }

            $user = $controller->show($params['id']);
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

        // Group Routes
        $this->router->get('/groups', function (Request $req) {
            $controller = $this->controllers['group'];
            $search = $req->get('search') ?? null;
            $data = $controller->index($search);
            $data['breadcrumbs'] = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Groups', 'url' => '/groups'],
            ];
            return new Response($this->view->renderWithLayout('groups/index', $data));
        });

        $this->router->get('/groups/create', function (Request $req) {
            $form = new GroupForm(null, [], $this->laminasSm);
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
            $form = new GroupForm(null, [], $this->laminasSm);
            $form->setData($req->all());

            if ($form->isValid()) {
                $controller = $this->controllers['group'];
                $controller->create($req->all());
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
            $controller = $this->controllers['group'];
            $group = $controller->show((int) $params['id']);
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
            $controller = $this->controllers['group'];
            $controller->delete((int) $params['id']);
            header('Location: /groups');
            exit;
        });

        $this->router->get('/groups/{id}/edit', function (Request $req, array $params) {
            $controller = $this->controllers['group'];
            $group = $controller->show((int) $params['id']);
            if (!$group) {
                return new Response('Group not found', 404);
            }
            $form = new GroupForm(null, [], $this->laminasSm);
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
            $form = new GroupForm(null, [], $this->laminasSm);
            $form->setData($req->all());

            if ($form->isValid()) {
                $controller = $this->controllers['group'];
                $group = $controller->update((int) $params['id'], $req->all());
                if (!$group) {
                    return new Response('Group not found', 404);
                }
                header('Location: /groups');
                exit;
            }

            $controller = $this->controllers['group'];
            $group = $controller->show((int) $params['id']);
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
}
