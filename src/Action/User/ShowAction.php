<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\GetUserCommand;
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
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        $command = new GetUserCommand(id: $id);
        $user = $this->commandBus->handle($command);

        if (!$user) {
            return JsonHalResponder::notFound('User not found');
        }

        $resource = new Item($user, new \App\Transformer\Resource\UserTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::resource(
            'user',
            $id,
            $data,
            [
                'collection' => '/api/users',
            ]
        );
    }
}
