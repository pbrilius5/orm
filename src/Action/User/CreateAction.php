<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\CreateUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Laminas\ServiceManager\ServiceManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;
    private ?ServiceManager $laminasSm;

    public function __construct(
        CommandBusInterface $commandBus,
        DtoFactory $dtoFactory,
        ?ServiceManager $laminasSm = null
    ) {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->laminasSm = $laminasSm;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if ($this->laminasSm === null) {
            if (empty($body['email']) || empty($body['password']) || empty($body['work_group'])) {
                $errors = [];
                if (empty($body['email'])) {
                    $errors[] = ['field' => 'email', 'message' => 'Email is required'];
                }
                if (empty($body['password'])) {
                    $errors[] = ['field' => 'password', 'message' => 'Password is required'];
                }
                if (empty($body['work_group'])) {
                    $errors[] = ['field' => 'work_group', 'message' => 'Work group is required'];
                }
                return JsonHalResponder::unprocessableEntity($errors);
            }

            $command = new CreateUserCommand(
                email: $body['email'],
                password: $body['password'],
                workGroup: $body['work_group'],
                roles: is_array($body['gamification_roles'])
                    ? $body['gamification_roles']
                    : ($body['gamification_roles'] ? [$body['gamification_roles']] : [])
            );

            $user = $this->commandBus->handle($command);
            $gamificationRoles = $user->getAllRoles();
            usort($gamificationRoles, fn($a, $b) => $b->getRank() <=> $a->getRank());

            $dto = $this->dtoFactory->create($user, [
                'userRoles' => $user->getUserRoles()->toArray(),
                'workGroup' => $user->getWorkGroups()[0] ?? null,
                'gamificationRoles' => $gamificationRoles,
            ]);

            return JsonHalResponder::created(
                'user',
                $user->getId()?->toString() ?? 'new',
                $dto,
                [
                    'collection' => '/api/users',
                    'self' => '/api/users/' . ($user->getId()?->toString() ?? 'new'),
                ]
            );
        }

        $workGroupMap = $this->laminasSm->get(\App\Service\WorkGroupMapInterface::class);
        $form = new \App\Form\UserForm(null, ['skip_csrf' => true], $this->laminasSm, $workGroupMap);
        $form->setData([
            'email' => $body['email'] ?? '',
            'password' => $body['password'] ?? '',
            'work_group' => $body['work_group'] ?? '',
            'gamification_roles' => $body['gamification_roles'] ?? '',
        ]);

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getMessages() as $field => $messages) {
                if ($field === 'work_group' || $field === 'gamification_roles') {
                    continue;
                }
                foreach ($messages as $message) {
                    $errors[] = ['field' => $field, 'message' => $message];
                }
            }
            if (!empty($errors)) {
                return JsonHalResponder::unprocessableEntity($errors);
            }
        }

        $data = $form->getData();
        $roles = $data['gamification_roles'];
        if (is_string($roles)) {
            $roles = $roles ? [$roles] : [];
        }

        $command = new CreateUserCommand(
            email: $data['email'],
            password: $data['password'],
            workGroup: $data['work_group'],
            roles: $roles
        );

        $user = $this->commandBus->handle($command);
        $gamificationRoles = $user->getAllRoles();
        usort($gamificationRoles, fn($a, $b) => $b->getRank() <=> $a->getRank());

        $dto = $this->dtoFactory->create($user, [
            'userRoles' => $user->getUserRoles()->toArray(),
            'workGroup' => $user->getWorkGroups()[0] ?? null,
            'gamificationRoles' => $gamificationRoles,
        ]);

        return JsonHalResponder::created(
            'user',
            $user->getId()?->toString() ?? 'new',
            $dto,
            [
                'collection' => '/api/users',
                'self' => '/api/users/' . ($user->getId()?->toString() ?? 'new'),
            ]
        );
    }
}
