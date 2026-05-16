<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\User\UpdateUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Laminas\ServiceManager\ServiceManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class UpdateAction extends AbstractAdrAction
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
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if ($this->laminasSm === null) {
            // Use FormProcessor to validate a lightweight form instance so ValidationException
            // will be converted to a 422 response by JsonErrorHandler middleware.
            $workGroupMap = new \App\Service\WorkGroupMap();
            $form = new \App\Form\UserForm(null, ['skip_csrf' => true], null, $workGroupMap);

            $data = $this->formProcessor->validateOrThrow($form, [
                'email' => $body['email'] ?? '',
                'password' => $body['password'] ?? '',
                'work_group' => $body['work_group'] ?? '',
                'gamification_roles' => $body['gamification_roles'] ?? '',
            ]);

            $command = new UpdateUserCommand(
                id: $id,
                email: $data['email'] ?? null,
                password: $data['password'] ?? null,
                workGroup: $data['work_group'] ?? null,
                roles: is_array($data['gamification_roles']) ? $data['gamification_roles'] : ($data['gamification_roles'] ? [$data['gamification_roles']] : [])
            );

            $user = $this->commandBus->handle($command);

            if (!$user) {
                return JsonHalResponder::notFound('User not found');
            }

            $gamificationRoles = $user->getGamificationRoles();
            usort($gamificationRoles, fn($a, $b) => $b->getRank() <=> $a->getRank());

            $dto = $this->dtoFactory->create($user, [
                'userRoles' => $user->getUserRoles()->toArray(),
                'workGroup' => $user->getWorkGroups()[0] ?? null,
                'gamificationRoles' => $gamificationRoles,
            ]);

            return JsonHalResponder::resource('user', $user->getId()?->toString() ?? '', $dto);
        }

        // Use FormProcessor for validation so ValidationException is handled globally
        $workGroupMap = $this->laminasSm->get(\App\Service\WorkGroupMapInterface::class);
        $data = $this->formProcessor->validateOrThrow(
            new \App\Form\UserForm(null, ['skip_csrf' => true], $this->laminasSm, $workGroupMap),
            [
                'email' => $body['email'] ?? '',
                'password' => $body['password'] ?? '',
                'work_group' => $body['work_group'] ?? '',
                'gamification_roles' => $body['gamification_roles'] ?? '',
            ]
        );

        $command = new UpdateUserCommand(
            id: $id,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            workGroup: $data['work_group'] ?? null,
            roles: is_array($data['gamification_roles']) ? $data['gamification_roles'] : ($data['gamification_roles'] ? [$data['gamification_roles']] : [])
        );

        $user = $this->commandBus->handle($command);

        if (!$user) {
            return JsonHalResponder::notFound('User not found');
        }

        $gamificationRoles = $user->getGamificationRoles();
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
