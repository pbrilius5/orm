<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Repository\GroupRepository;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Transformer\Resource\GroupTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListAction
{
    private GroupRepository $repository;
    private Manager $fractal;

    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
        $this->fractal = new Manager();
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $groups = $this->repository->findAll();

        $resource = new Collection($groups, new GroupTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::collection('groups', $data['data'] ?? [], [
            'total' => count($data['data'] ?? []),
            'count' => count($data['data'] ?? []),
        ]);
    }
}
