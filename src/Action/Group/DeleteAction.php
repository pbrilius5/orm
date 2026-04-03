<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Repository\GroupRepository;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class DeleteAction
{
    private GroupRepository $repository;

    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid group ID provided');
        }

        $uuid = Uuid::fromString($id);
        $group = $this->repository->find($uuid);

        if (!$group) {
            return JsonHalResponder::notFound('Group not found');
        }

        $this->repository->delete($group);

        return JsonHalResponder::noContent();
    }
}
