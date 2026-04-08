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
use Psr\Log\LoggerInterface;

class GroupController
{
    private EntityManager $em;
    private GroupRepository $repository;
    private CommandBus $commandBus;
    private DtoFactory $dtoFactory;
    private LoggerInterface $logger;

    public function __construct(EntityManager $em, CommandBus $commandBus, DtoFactory $dtoFactory, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->commandBus = $commandBus;
        $this->dtoFactory = $dtoFactory;
        $this->logger = $logger;
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
        $this->logger->debug('Creating group', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $command = new CreateGroupCommand(
            name: $data['name'],
            description: $data['description'] ?? null
        );

        $this->logger->debug('Created CreateGroupCommand', [
            'name' => $command->name,
            'description' => $command->description,
        ]);

        $result = $this->commandBus->handle($command);

        $this->logger->debug('Group created successfully', [
            'groupId' => $result->getId(),
            'name' => $result->getName(),
        ]);

        return $result;
    }

    public function update(int $id, array $data): ?Group
    {
        $this->logger->debug('Updating group', [
            'groupId' => $id,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
        ]);

        $command = new UpdateGroupCommand(
            id: (string) $id,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null
        );

        $this->logger->debug('Created UpdateGroupCommand', [
            'id' => $command->id,
            'name' => $command->name,
            'description' => $command->description,
        ]);

        $result = $this->commandBus->handle($command);

        if ($result === null) {
            $this->logger->warning('Group not found for update', [
                'groupId' => $id,
            ]);
        } else {
            $this->logger->debug('Group updated successfully', [
                'groupId' => $result->getId(),
                'name' => $result->getName(),
            ]);
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        $this->logger->debug('Deleting group', [
            'groupId' => $id,
        ]);

        $command = new DeleteGroupCommand(id: (string) $id);

        $this->logger->debug('Created DeleteGroupCommand', [
            'id' => $command->id,
        ]);

        $result = $this->commandBus->handle($command);

        $this->logger->debug('Group deletion processed', [
            'groupId' => $id,
            'success' => $result,
        ]);

        return $result;
    }
}
