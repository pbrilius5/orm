<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\UpdateUserCommand;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class UpdateAction
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

        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        $command = new UpdateUserCommand(
            id: $id,
            email: $body['email'] ?? "user-{$id}@example.com",
            password: $body['password'] ?? 'password',
            groupId: $body['group_id'] ?? null,
            roles: $body['roles'] ?? []
        );

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
                'self' => "/api/users/{$id}",
            ]
        );
    }
}
