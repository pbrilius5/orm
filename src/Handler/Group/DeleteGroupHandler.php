<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\DeleteGroupCommand;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class DeleteGroupHandler
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

    public function handle(DeleteGroupCommand $command): bool
    {
        $this->logger?->info('Deleting group: ' . $command->id);
        $this->logger?->debug('Deleting group details', [
            'groupId' => $command->id,
        ]);

        $group = $this->repository->find($command->id);
        if (!$group) {
            $this->logger?->warning('Group not found for deletion', [
                'groupId' => $command->id,
            ]);
            return false;
        }

        $this->logger?->debug('Found group for deletion', [
            'groupId' => $group->getId(),
            'name' => $group->getName(),
        ]);

        $this->em->remove($group);
        $this->logger?->debug('Group marked for removal');

        $this->em->flush();
        $this->logger?->info('Group deleted: ' . $command->id);
        $this->logger?->debug('Group deletion completed');

        return true;
    }
}
