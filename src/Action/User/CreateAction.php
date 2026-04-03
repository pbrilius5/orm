<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\CreateUserCommand;
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

        if (empty($body['email']) || empty($body['password'])) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'email', 'message' => 'Email is required'],
                ['field' => 'password', 'message' => 'Password is required'],
            ]);
        }

        $command = new CreateUserCommand(
            email: $body['email'],
            password: $body['password'],
            groupId: $body['group_id'] ?? null,
            roles: $body['roles'] ?? []
        );

        $user = $this->commandBus->handle($command);

        $resource = new Item($user, new \App\Transformer\Resource\UserTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::created(
            'user',
            $user->getId()?->toString() ?? 'new',
            $data,
            [
                'collection' => '/api/users',
                'self' => '/api/users/' . ($user->getId()?->toString() ?? 'new'),
            ]
        );
    }
}
