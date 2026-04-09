<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\PatchUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Laminas\ServiceManager\ServiceManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class PatchAction
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
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if (empty($body)) {
            return JsonHalResponder::unprocessableEntity([['field' => 'body', 'message' => 'No fields provided']]);
        }

        if ($this->laminasSm === null) {
            return JsonHalResponder::error('Service Unavailable', 503);
        }

        $workGroupMap = $this->laminasSm->get(\App\Service\WorkGroupMapInterface::class);
        $form = new \App\Form\UserForm(null, ['skip_csrf' => true], $this->laminasSm, $workGroupMap);

        $formData = [
            'email' => $body['email'] ?? '',
            'password' => $body['password'] ?? '',
            'work_group' => $body['work_group'] ?? '',
            'gamification_roles' => $body['gamification_roles'] ?? '',
        ];
        $form->setData($formData);

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

        $command = new PatchUserCommand(
            id: $id,
            email: $body['email'] ?? null,
            password: $body['password'] ?? null,
            workGroup: $body['work_group'] ?? null,
            roles: $body['gamification_roles'] ?? null
        );

        $user = $this->commandBus->handle($command);

        if (!$user) {
            return JsonHalResponder::notFound('User not found');
        }

        $gamificationRoles = $user->getAllRoles();
        usort($gamificationRoles, fn($a, $b) => $b->getRank() <=> $a->getRank());

        $dto = $this->dtoFactory->create($user, [
            'userRoles' => $user->getUserRoles()->toArray(),
            'workGroup' => $user->getWorkGroups()[0] ?? null,
            'gamificationRoles' => $gamificationRoles,
        ]);

        return JsonHalResponder::resource(
            'user',
            $id,
            $dto,
            [
                'collection' => '/api/users',
                'self' => "/api/users/{$id}",
            ]
        );
    }
}
