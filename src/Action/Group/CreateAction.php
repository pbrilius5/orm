<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\Group\CreateGroupCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction extends AbstractAdrAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;

    public function __construct(CommandBusInterface $commandBus, DtoFactory $dtoFactory)
    {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, ?callable $next = null): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if (empty($body['name'])) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'name', 'message' => 'Name is required'],
            ]);
        }

        $command = new CreateGroupCommand(
            name: $body['name'],
            description: $body['description'] ?? null,
            type: $body['type'] ?? null
        );

        $group = $this->commandBus->handle($command);
        $dto = $this->dtoFactory->create($group, [
            'users' => $group->getUsers()->toArray(),
        ]);

        return JsonHalResponder::created(
            'group',
            $group->getId()?->toString() ?? 'new',
            $dto,
            [
                'collection' => '/api/groups',
                'self' => '/api/groups/' . ($group->getId()?->toString() ?? 'new'),
            ]
        );
    }
}
