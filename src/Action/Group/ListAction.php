<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\Group\ListGroupsCommand;
use App\Dto\DtoFactory;
use App\Repository\GroupRepository;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListAction extends AbstractAdrAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;
    private GroupRepository $groupRepository;

    public function __construct(
        CommandBusInterface $commandBus,
        DtoFactory $dtoFactory,
        GroupRepository $groupRepository
    ) {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->groupRepository = $groupRepository;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, ?callable $next = null): ResponseInterface
    {
        $limit = $request->getQueryParams()['limit'] ?? null;
        $offset = $request->getQueryParams()['offset'] ?? null;
        $search = $request->getQueryParams()['search'] ?? null;

        $limit = $limit !== null ? (int) $limit : null;
        $offset = $offset !== null ? (int) $offset : null;

        $total = $search !== null && $search !== ''
            ? $this->groupRepository->countAllWithFilter($search)
            : $this->groupRepository->countAll();

        $command = new ListGroupsCommand(
            limit: $limit,
            offset: $offset,
            search: $search
        );

        $groups = $this->commandBus->handle($command);

        $dtos = array_map(
            fn($group) => $this->dtoFactory->create($group, [
                'users' => [], // deprecated
                'gamificationRoles' => [], // deprecated
            ]),
            $groups
        );

        return JsonHalResponder::collection('groups', $dtos, [
            'total' => $total,
            'count' => count($dtos),
        ]);
    }
}
