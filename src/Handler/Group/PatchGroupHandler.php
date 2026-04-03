<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\PatchGroupCommand;
use App\Entity\Group;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class PatchGroupHandler
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

    public function handle(PatchGroupCommand $command): ?Group
    {
        $this->logger?->info('Patching group: ' . $command->id);

        $group = $this->repository->find($command->id);
        if (!$group) {
            $this->logger?->warning('Group not found: ' . $command->id);
            return null;
        }

        if ($command->name !== null) {
            $group->setName($command->name);
        }
        if ($command->description !== null) {
            $group->setDescription($command->description);
        }

        $this->em->flush();

        $this->logger?->info('Group patched: ' . $command->id);

        return $group;
    }
}
