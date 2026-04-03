<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Command\CommandBusInterface;
use App\Command\Group\CreateGroupCommand;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction
{
    private CommandBusInterface $commandBus;
    private Manager $fractal;

    public function __construct(CommandBusInterface $commandBus, Manager $fractal)
    {
        $this->commandBus = $commandBus;
        $this->fractal = $fractal;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
            description: $body['description'] ?? null
        );

        $group = $this->commandBus->handle($command);

        $resource = new Item($group, new \App\Transformer\Resource\GroupTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::created(
            'group',
            $group->getId()?->toString() ?? 'new',
            $data,
            [
                'collection' => '/api/groups',
                'self' => '/api/groups/' . ($group->getId()?->toString() ?? 'new'),
            ]
        );
    }
}
