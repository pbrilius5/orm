<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\PatchUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Laminas\Validator\EmailAddress;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class PatchAction
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
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if (empty($body)) {
            return JsonHalResponder::unprocessableEntity([['field' => 'body', 'message' => 'No fields provided']]);
        }

        if (isset($body['email']) && $body['email'] !== '') {
            $emailValidator = new EmailAddress();
            if (!$emailValidator->isValid($body['email'])) {
                return JsonHalResponder::unprocessableEntity([
                    ['field' => 'email', 'message' => 'Email must be a valid email address'],
                ]);
            }
        }

        if (isset($body['password']) && $body['password'] !== '' && strlen($body['password']) < 6) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'password', 'message' => 'Password must be at least 6 characters'],
            ]);
        }

        if (isset($body['work_group']) && $body['work_group'] !== '' && !in_array($body['work_group'], ['developer', 'designer', 'tester'], true)) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'work_group', 'message' => 'Work group must be one of: developer, designer, tester'],
            ]);
        }

        $command = new PatchUserCommand(
            id: $id,
            email: $body['email'] ?? null,
            password: $body['password'] ?? null,
            workGroup: $body['work_group'] ?? null,
            roles: $body['gamification_roles'] ?? null
        );

        $user = $this->commandBus->handle($command);

        if (!$user) {
            return JsonHalResponder::notFound('User not found');
        }

        $gamificationRoles = $user->getAllRoles();
        usort($gamificationRoles, fn($a, $b) => $b->getRank() <=> $a->getRank());

        $dto = $this->dtoFactory->create($user, [
            'userRoles' => $user->getUserRoles()->toArray(),
            'workGroup' => $user->getWorkGroups()[0] ?? null,
            'gamificationRoles' => $gamificationRoles,
        ]);

        return JsonHalResponder::resource(
            'user',
            $id,
            $dto,
            [
                'collection' => '/api/users',
                'self' => "/api/users/{$id}",
            ]
        );
    }
}
