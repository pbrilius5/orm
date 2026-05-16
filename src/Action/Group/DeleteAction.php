<?php

declare(strict_types=1);

namespace App\Action\Group;

use App\Action\AbstractAdrAction;
use App\Command\CommandBusInterface;
use App\Command\Group\DeleteGroupCommand;
use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class DeleteAction extends AbstractAdrAction
{
    private CommandBusInterface $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, ?callable $next = null): ResponseInterface
    {
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid group ID provided');
        }

        $command = new DeleteGroupCommand(id: $id);
        $result = $this->commandBus->handle($command);

        if (!$result) {
            return JsonHalResponder::notFound('Group not found');
        }

        return JsonHalResponder::noContent();
    }
}
