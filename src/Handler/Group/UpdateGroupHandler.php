<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\UpdateGroupCommand;
use App\Entity\Group;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class UpdateGroupHandler
{
    private EntityManager $em;
    private GroupRepository $repository;
    private ?LoggerInterface $logger;

    public function __construct(EntityManager $em, ?LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->repository = new GroupRepository($em);
        $this->logger = $logger;
    }

    public function handle(UpdateGroupCommand $command): ?Group
    {
        $this->logger?->info('Updating group: ' . $command->id);
        $this->logger?->debug('Updating group details', [
            'groupId' => $command->id,
            'name' => $command->name,
            'description' => $command->description,
        ]);

        $group = $this->repository->find($command->id);
        if (!$group) {
            $this->logger?->warning('Group not found for update', [
                'groupId' => $command->id,
            ]);
            return null;
        }

        $this->logger?->debug('Found group for update', [
            'groupId' => $group->getId(),
            'currentName' => $group->getName(),
            'currentDescription' => $group->getDescription(),
        ]);

        $group->setName($command->name);
        if ($command->description !== null) {
            $group->setDescription($command->description);
        }

        $this->logger?->debug('Group properties updated');

        $this->em->flush();
        $this->logger?->info('Group updated: ' . $command->id);
        $this->logger?->debug('Group update completed', [
            'groupId' => $group->getId(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
        ]);

        return $group;
    }
}
