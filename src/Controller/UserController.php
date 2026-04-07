<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\DtoFactory;
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
    private DtoFactory $dtoFactory;

    private const GAMIFICATION_ROLE_CLASSES = [
        WizardRole::NAME => WizardRole::class,
        ArchitectRole::NAME => ArchitectRole::class,
        GameMasterRole::NAME => GameMasterRole::class,
    ];

    public function __construct(EntityManager $em, CommandBus $commandBus, DtoFactory $dtoFactory)
    {
        $this->em = $em;
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->repository = new UserRepository($em);
        $this->groupRepository = new GroupRepository($em);
    }

    public function getGroups(): array
    {
        return $this->groupRepository->findAll();
    }

    public function getWorkGroups(): array
    {
        return array_values(array_filter(
            $this->groupRepository->findAll(),
            fn($g) => $g->isWorkGroup()
        ));
    }

    public function index(): array
    {
        $users = $this->repository->findAll();
        return ['users' => array_map(
            fn($user) => $this->dtoFactory->create($user, [
                'workGroup' => $user->getWorkGroup(),
                'gamificationRoles' => $user->getGamificationRoles(),
            ]),
            $users
        )];
    }

    public function show(string $id): ?User
    {
        return $this->repository->find($id);
    }

    public function showDto(string $id): ?array
    {
        $user = $this->repository->find($id);
        if (!$user) {
            return null;
        }
        return ['user' => $this->dtoFactory->create($user)];
    }

    public function create(array $data): User
    {
        $roles = $data['gamification_roles'] ?? [];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $command = new CreateUserCommand(
            email: $data['email'],
            password: $data['password'] ?? '',
            groupId: $data['work_group_id'] ?? null,
            roles: $roles
        );

        return $this->commandBus->handle($command);
    }

    public function update(string $id, array $data): ?User
    {
        $command = new UpdateUserCommand(
            id: $id,
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            groupId: $data['work_group_id'] ?? null,
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
