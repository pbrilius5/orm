<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Command\CommandBusInterface;
use App\Command\Group\ListGroupsCommand;
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

        $command = new ListGroupsCommand(
            limit: $limit !== null ? (int) $limit : null,
            offset: $offset !== null ? (int) $offset : null,
            search: $search
        );

        $groups = $this->commandBus->handle($command);

        $dtos = array_map(
            fn($group) => $this->dtoFactory->create($group, [
                'users' => $group->getUsers()->toArray(),
                'gamificationRoles' => $group->getGamificationRoles(),
            ]),
            $groups
        );

        return JsonHalResponder::collection('groups', $dtos, [
            'total' => count($dtos),
            'count' => count($dtos),
        ]);
    }
}
