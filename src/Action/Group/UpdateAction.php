<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Command\CommandBusInterface;
use App\Command\Group\UpdateGroupCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class UpdateAction
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
            return JsonHalResponder::badRequest('Invalid group ID provided');
        }

        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        $command = new UpdateGroupCommand(
            id: $id,
            name: $body['name'] ?? '',
            description: $body['description'] ?? null
        );

        $group = $this->commandBus->handle($command);

        if (!$group) {
            return JsonHalResponder::notFound('Group not found');
        }

        $dto = $this->dtoFactory->create($group, [
            'users' => $group->getUsers()->toArray(),
            'gamificationRoles' => $group->getGamificationRoles(),
        ]);

        return JsonHalResponder::resource(
            'group',
            $id,
            $dto,
            [
                'collection' => '/api/groups',
                'self' => "/api/groups/{$id}",
            ]
        );
    }
}
