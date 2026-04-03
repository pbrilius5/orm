<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\Group;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\WizardRole;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Command\User\CreateUserCommand;
use App\Command\User\UpdateUserCommand;
use App\Command\User\PatchUserCommand;
use App\Command\User\DeleteUserCommand;
use App\Command\User\GetUserCommand;
use App\Command\User\ListUsersCommand;
use Oryx\ORM\EntityManager;
use League\Tactician\CommandBus;

class UserController
{
    private EntityManager $em;
    private UserRepository $repository;
    private GroupRepository $groupRepository;
    private CommandBus $commandBus;

    private const GAMIFICATION_ROLE_CLASSES = [
        WizardRole::NAME => WizardRole::class,
        ArchitectRole::NAME => ArchitectRole::class,
        GameMasterRole::NAME => GameMasterRole::class,
    ];

    public function __construct(EntityManager $em, CommandBus $commandBus)
    {
        $this->em = $em;
        $this->commandBus = $commandBus;
        $this->repository = new UserRepository($em);
        $this->groupRepository = new GroupRepository($em);
    }

    public function getGroups(): array
    {
        return $this->groupRepository->findAll();
    }

    public function index(): array
    {
        $users = $this->repository->findAll();
        return ['users' => $users];
    }

    public function show(string $id): ?User
    {
        return $this->repository->find($id);
    }

    public function create(array $data): User
    {
        $command = new CreateUserCommand(
            email: $data['email'],
            password: $data['password'] ?? '',
            groupId: $data['group_id'] ?? null,
            roles: $data['gamification_roles'] ?? []
        );

        return $this->commandBus->handle($command);
    }

    public function update(string $id, array $data): ?User
    {
        $command = new UpdateUserCommand(
            id: $id,
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            groupId: $data['group_id'] ?? null,
            roles: $data['gamification_roles'] ?? []
        );

        return $this->commandBus->handle($command);
    }

    public function delete(string $id): bool
    {
        $command = new DeleteUserCommand(id: $id);
        return $this->commandBus->handle($command);
    }
}
