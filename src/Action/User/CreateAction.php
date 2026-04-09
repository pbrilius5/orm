<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Command\CommandBusInterface;
use App\Command\User\CreateUserCommand;
use App\Dto\DtoFactory;
use App\Responder\JsonHalResponder;
use Laminas\Validator\EmailAddress;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAction
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
        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if (empty($body['email']) || empty($body['password']) || empty($body['work_group'])) {
            $errors = [];
            if (empty($body['email'])) {
                $errors[] = ['field' => 'email', 'message' => 'Email is required'];
            }
            if (empty($body['password'])) {
                $errors[] = ['field' => 'password', 'message' => 'Password is required'];
            }
            if (empty($body['work_group'])) {
                $errors[] = ['field' => 'work_group', 'message' => 'Work group is required'];
            }
            return JsonHalResponder::unprocessableEntity($errors);
        }

        $emailValidator = new EmailAddress();
        if (!$emailValidator->isValid($body['email'])) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'email', 'message' => 'Email must be a valid email address'],
            ]);
        }

        if (strlen($body['password']) < 6) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'password', 'message' => 'Password must be at least 6 characters'],
            ]);
        }

        if (!in_array($body['work_group'], ['developer', 'designer', 'tester'], true)) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'work_group', 'message' => 'Work group must be one of: developer, designer, tester'],
            ]);
        }

        $command = new CreateUserCommand(
            email: $body['email'],
            password: $body['password'],
            workGroup: $body['work_group'],
            roles: is_array($body['gamification_roles'])
                ? $body['gamification_roles']
                : ($body['gamification_roles'] ? [$body['gamification_roles']] : [])
        );

        $user = $this->commandBus->handle($command);
        $gamificationRoles = $user->getAllRoles();
        usort($gamificationRoles, fn($a, $b) => $b->getRank() <=> $a->getRank());

        $dto = $this->dtoFactory->create($user, [
            'userRoles' => $user->getUserRoles()->toArray(),
            'workGroup' => $user->getWorkGroups()[0] ?? null,
            'gamificationRoles' => $gamificationRoles,
        ]);

        return JsonHalResponder::created(
            'user',
            $user->getId()?->toString() ?? 'new',
            $dto,
            [
                'collection' => '/api/users',
                'self' => '/api/users/' . ($user->getId()?->toString() ?? 'new'),
            ]
        );
    }
}
