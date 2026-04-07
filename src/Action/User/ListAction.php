<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\ListUsersCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListAction
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
        $limit = $request->getQueryParams()['limit'] ?? null;
        $offset = $request->getQueryParams()['offset'] ?? null;
        $search = $request->getQueryParams()['search'] ?? null;

        $command = new ListUsersCommand(
            limit: $limit !== null ? (int) $limit : null,
            offset: $offset !== null ? (int) $offset : null,
            search: $search
        );

        $users = $this->commandBus->handle($command);

        $dtos = array_map(
            fn($user) => $this->dtoFactory->create($user, [
                'userRoles' => $user->getUserRoles()->toArray(),
                'workGroup' => $user->getWorkGroups()[0] ?? null,
                'gamificationRoles' => $user->getGamificationRoles(),
            ]),
            $users
        );

        return JsonHalResponder::collection('users', $dtos, [
            'total' => count($dtos),
            'count' => count($dtos),
        ]);
    }
}
