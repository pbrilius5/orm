<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\Group\GetGroupCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class ShowAction extends AbstractAdrAction
{
    private CommandBusInterface $commandBus;
    private DtoFactory $dtoFactory;

    public function __construct(CommandBusInterface $commandBus, DtoFactory $dtoFactory)
    {
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, ?callable $next = null): ResponseInterface
    {
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid group ID provided');
        }

        $command = new GetGroupCommand(id: $id);
        $group = $this->commandBus->handle($command);

        if (!$group) {
            return JsonHalResponder::notFound('Group not found');
        }

        $dto = $this->dtoFactory->create($group, [
            'users' => $group->getUsers()->toArray(),
            'gamificationRoles' => $group->getGamificationRoles(),
        ]);

        return JsonHalResponder::resource(
            'group',
            $id,
            $dto,
            [
                'collection' => '/api/groups',
            ]
        );
    }
}
