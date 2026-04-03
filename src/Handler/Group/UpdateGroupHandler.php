<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\UpdateGroupCommand;
use App\DTO\GroupApiDTO;
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

    public function handle(UpdateGroupCommand $command): ?GroupApiDTO
    {
        $this->logger?->info('Updating group: ' . $command->id);

        $group = $this->repository->find($command->id);
        if (!$group) {
            $this->logger?->warning('Group not found: ' . $command->id);
            return null;
        }

        $group->setName($command->name);
        if ($command->description !== null) {
            $group->setDescription($command->description);
        }

        $this->em->flush();

        $this->logger?->info('Group updated: ' . $command->id);

        return $this->repository->findForApi($command->id);
    }
}
