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
use Psr\Log\LoggerInterface;

class UserController
{
    private EntityManager $em;
    private UserRepository $repository;
    private GroupRepository $groupRepository;
    private CommandBus $commandBus;
    private DtoFactory $dtoFactory;
    private LoggerInterface $logger;

    private const GAMIFICATION_ROLE_CLASSES = [
        WizardRole::NAME => WizardRole::class,
        ArchitectRole::NAME => ArchitectRole::class,
        GameMasterRole::NAME => GameMasterRole::class,
    ];

    public function __construct(EntityManager $em, CommandBus $commandBus, DtoFactory $dtoFactory, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->logger = $logger;
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
        $this->logger->debug('Creating user', [
            'email' => $data['email'] ?? '',
            'hasPassword' => !empty($data['password']),
            'workGroup' => $data['work_group'] ?? null,
            'roles' => $data['gamification_roles'] ?? [],
        ]);

        $roles = $data['gamification_roles'] ?? [];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $command = new CreateUserCommand(
            email: $data['email'],
            password: $data['password'] ?? '',
            workGroup: $data['work_group'] ?? null,
            roles: $roles
        );

        $this->logger->debug('Created CreateUserCommand', [
            'email' => $command->email,
            'workGroup' => $command->workGroup,
            'roles' => $command->roles,
        ]);

        $result = $this->commandBus->handle($command);

        $this->logger->debug('User created successfully', [
            'userId' => $result->getId(),
            'email' => $result->getEmail(),
        ]);

        return $result;
    }

    public function update(string $id, array $data): ?User
    {
        $this->logger->debug('Updating user', [
            'userId' => $id,
            'email' => $data['email'] ?? '',
            'hasPassword' => !empty($data['password']),
            'workGroup' => $data['work_group'] ?? null,
            'roles' => $data['gamification_roles'] ?? [],
        ]);

        $roles = $data['gamification_roles'] ?? [];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $command = new UpdateUserCommand(
            id: $id,
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            workGroup: $data['work_group'] ?? null,
            roles: $roles
        );

        $this->logger->debug('Created UpdateUserCommand', [
            'id' => $command->id,
            'email' => $command->email,
            'workGroup' => $command->workGroup,
            'roles' => $command->roles,
        ]);

        $result = $this->commandBus->handle($command);

        if ($result === null) {
            $this->logger->warning('User not found for update', [
                'userId' => $id,
            ]);
        } else {
            $this->logger->debug('User updated successfully', [
                'userId' => $result->getId(),
                'email' => $result->getEmail(),
            ]);
        }

        return $result;
    }

    public function delete(string $id): bool
    {
        $this->logger->debug('Deleting user', [
            'userId' => $id,
        ]);

        $command = new DeleteUserCommand(id: $id);

        $this->logger->debug('Created DeleteUserCommand', [
            'id' => $command->id,
        ]);

        $result = $this->commandBus->handle($command);

        $this->logger->debug('User deletion processed', [
            'userId' => $id,
            'success' => $result,
        ]);

        return $result;
    }
}
     return $result;
    }
}
