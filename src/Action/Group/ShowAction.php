<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Command\CommandBusInterface;
use App\Command\Group\GetGroupCommand;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class ShowAction
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
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid group ID provided');
        }

        $command = new GetGroupCommand(id: $id);
        $group = $this->commandBus->handle($command);

        if (!$group) {
            return JsonHalResponder::notFound('Group not found');
        }

        $resource = new Item($group, new \App\Transformer\Resource\GroupTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::resource(
            'group',
            $id,
            $data,
            [
                'collection' => '/api/groups',
            ]
        );
    }
}
