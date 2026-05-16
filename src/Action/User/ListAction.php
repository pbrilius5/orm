<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\User\ListUsersCommand;
use App\Dto\DtoFactory;
use App\Repository\UserRepository;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListAction extends AbstractAdrAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;
    private UserRepository $userRepository;

    public function __construct(
        CommandBusInterface $commandBus,
        DtoFactory $dtoFactory,
        UserRepository $userRepository
    ) {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->userRepository = $userRepository;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, ?callable $next = null): ResponseInterface
    {
        $limit = $request->getQueryParams()['limit'] ?? null;
        $offset = $request->getQueryParams()['offset'] ?? null;
        $search = $request->getQueryParams()['search'] ?? null;

        $limit = $limit !== null ? (int) $limit : null;
        $offset = $offset !== null ? (int) $offset : null;

        $total = $search !== null && $search !== ''
            ? $this->userRepository->countAllWithFilter($search)
            : $this->userRepository->countAll();

        $command = new ListUsersCommand(
            limit: $limit,
            offset: $offset,
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
            'total' => $total,
            'count' => count($dtos),
        ]);
    }
}
