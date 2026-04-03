<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Transformer\Resource\GroupTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction
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
        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if (empty($body['name'])) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'name', 'message' => 'Name is required'],
            ]);
        }

        $group = new Group();
        $group->setName($body['name']);
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->repository->save($group);

        $resource = new Item($group, new GroupTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::created(
            'group',
            'new',
            $data,
            [
                'collection' => '/api/groups',
                'self' => "/api/groups/{$group->getId()}",
            ]
        );
    }
}
