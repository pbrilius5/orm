<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Repository\GroupRepository;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Transformer\Resource\GroupTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowAction
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
        $id = (int) ($request->getAttribute('id') ?? 0);

        if ($id <= 0) {
            return JsonHalResponder::badRequest('Invalid group ID provided');
        }

        $group = $this->repository->find($id);

        if (!$group) {
            return JsonHalResponder::notFound('Group not found');
        }

        $resource = new Item($group, new GroupTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::resource(
            'group',
            (string) $id,
            $data,
            [
                'collection' => '/api/groups',
            ]
        );
    }
}
