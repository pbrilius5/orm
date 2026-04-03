<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\ListUsersCommand;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListAction
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
        $limit = $request->getQueryParams()['limit'] ?? null;
        $offset = $request->getQueryParams()['offset'] ?? null;
        $search = $request->getQueryParams()['search'] ?? null;

        $command = new ListUsersCommand(
            limit: $limit ? (int) $limit : null,
            offset: $offset ? (int) $offset : null,
            search: $search
        );

        $users = $this->commandBus->handle($command);

        $resource = new Collection($users, new \App\Transformer\Resource\UserTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::collection('users', $data['data'] ?? [], [
            'total' => count($data['data'] ?? []),
            'count' => count($data['data'] ?? []),
        ]);
    }
}
