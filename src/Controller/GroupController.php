<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\DtoFactory;
use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Command\Group\CreateGroupCommand;
use App\Command\Group\UpdateGroupCommand;
use App\Command\Group\PatchGroupCommand;
use App\Command\Group\DeleteGroupCommand;
use App\Command\Group\GetGroupCommand;
use App\Command\Group\ListGroupsCommand;
use Oryx\ORM\EntityManager;
use League\Tactician\CommandBus;

class GroupController
{
    private EntityManager $em;
    private GroupRepository $repository;
    private CommandBus $commandBus;
    private DtoFactory $dtoFactory;

    public function __construct(EntityManager $em, CommandBus $commandBus, DtoFactory $dtoFactory)
    {
        $this->em = $em;
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->repository = new GroupRepository($em);
    }

    public function index(?string $search = null): array
    {
        $command = new ListGroupsCommand(search: $search);
        $groups = $this->commandBus->handle($command);
        return ['groups' => array_map(
            fn($group) => $this->dtoFactory->create($group),
            $groups
        ), 'search' => $search];
    }

    public function show(int $id): ?Group
    {
        $command = new GetGroupCommand(id: (string) $id);
        return $this->commandBus->handle($command);
    }

    public function showDto(int $id): ?array
    {
        $group = $this->show($id);
        if (!$group) {
            return null;
        }
        return ['group' => $this->dtoFactory->create($group)];
    }

    public function create(array $data): Group
    {
        $command = new CreateGroupCommand(
            name: $data['name'],
            description: $data['description'] ?? null
        );
        return $this->commandBus->handle($command);
    }

    public function update(int $id, array $data): ?Group
    {
        $command = new UpdateGroupCommand(
            id: (string) $id,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null
        );
        return $this->commandBus->handle($command);
    }

    public function delete(int $id): bool
    {
        $command = new DeleteGroupCommand(id: (string) $id);
        return $this->commandBus->handle($command);
    }
}
