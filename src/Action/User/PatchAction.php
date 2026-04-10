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

        // Build data array with only the fields that are present in the request
        $patchData = [];
        if (array_key_exists('email', $body)) {
            $patchData['email'] = $body['email'];
        }
        if (array_key_exists('password', $body)) {
            $patchData['password'] = $body['password'];
        }
        if (array_key_exists('work_group', $body)) {
            $patchData['work_group'] = $body['work_group'];
        }
        if (array_key_exists('gamification_roles', $body)) {
            $patchData['gamification_roles'] = $body['gamification_roles'];
        }

        if ($this->laminasSm === null) {
            // Use FormProcessor for validation so ValidationException is handled globally
            $workGroupMap = new \App\Service\WorkGroupMap();
            $form = new \App\Form\UserForm(null, ['skip_csrf' => true, 'isPatch' => true], null, $workGroupMap);

            $data = $this->formProcessor->validateOrThrow($form, $patchData);

            $command = new PatchUserCommand(
                id: $id,
                email: $data['email'] ?? null,
                password: $data['password'] ?? null,
                workGroup: $data['work_group'] ?? null,
                roles: is_array($data['gamification_roles']) ? $data['gamification_roles'] : ($data['gamification_roles'] ? [$data['gamification_roles']] : null)
            );
        } else {
            // Use FormProcessor for validation so ValidationException is handled globally
            $workGroupMap = $this->laminasSm->get(\App\Service\WorkGroupMapInterface::class);
            $data = $this->formProcessor->validateOrThrow(
                new \App\Form\UserForm(null, ['skip_csrf' => true, 'isPatch' => true], $this->laminasSm, $workGroupMap),
                $patchData
            );

            $command = new PatchUserCommand(
                id: $id,
                email: $data['email'] ?? null,
                password: $data['password'] ?? null,
                workGroup: $data['work_group'] ?? null,
                roles: is_array($data['gamification_roles']) ? $data['gamification_roles'] : ($data['gamification_roles'] ? [$data['gamification_roles']] : null)
            );
        }

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
