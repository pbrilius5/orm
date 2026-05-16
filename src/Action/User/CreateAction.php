<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\User\CreateUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Laminas\ServiceManager\ServiceManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction extends AbstractAdrAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;
    private ?ServiceManager $laminasSm;
    private \App\Service\FormProcessor $formProcessor;

    public function __construct(
        CommandBusInterface $commandBus,
        DtoFactory $dtoFactory,
        \App\Service\FormProcessor $formProcessor,
        ?ServiceManager $laminasSm = null
    ) {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->formProcessor = $formProcessor;
        $this->laminasSm = $laminasSm;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, ?callable $next = null): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if ($this->laminasSm === null) {
            // Create a simple form instance and use the central FormProcessor to validate.
            $workGroupMap = new \App\Service\WorkGroupMap();
            $form = new \App\Form\UserForm(null, ['skip_csrf' => true], null, $workGroupMap);

            try {
                $data = $this->formProcessor->validateOrThrow($form, [
                    'email' => $body['email'] ?? '',
                    'password' => $body['password'] ?? '',
                    'work_group' => $body['work_group'] ?? '',
                    'gamification_roles' => $body['gamification_roles'] ?? '',
                ]);
            } catch (\App\Exception\ValidationException $e) {
                return JsonHalResponder::unprocessableEntity($e->getErrors());
            }

            $command = new CreateUserCommand(
                email: $data['email'],
                password: $data['password'],
                workGroup: $data['work_group'],
                roles: is_array($data['gamification_roles'])
                    ? $data['gamification_roles']
                    : ($data['gamification_roles'] ? [$data['gamification_roles']] : [])
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
