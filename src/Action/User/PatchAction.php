<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\PatchUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class PatchAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;

    public function __construct(CommandBusInterface $commandBus, DtoFactory $dtoFactory)
    {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
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

        $command = new PatchUserCommand(
            id: $id,
            email: $body['email'] ?? null,
            password: $body['password'] ?? null,
            groupId: $body['group_id'] ?? null,
            roles: $body['roles'] ?? null
        );

        $user = $this->commandBus->handle($command);

        if (!$user) {
            return JsonHalResponder::notFound('User not found');
        }

        $dto = $this->dtoFactory->create($user, [
            'userRoles' => $user->getUserRoles()->toArray(),
            'workGroup' => $user->getWorkGroups()[0] ?? null,
            'gamificationRoles' => [],
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
