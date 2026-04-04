<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\CreateUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction
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
        $dto = $this->dtoFactory->create($user);

        return JsonHalResponder::created(
            'user',
            $user->getId()?->toString() ?? 'new',
            $dto,
            [
                'collection' => '/api/users',
                'self' => '/api/users/' . ($user->getId()?->toString() ?? 'new'),
            ]
        );
    }
}
