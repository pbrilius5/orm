<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\CreateGroupCommand;
use App\Entity\Group;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class CreateGroupHandler
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

    public function handle(CreateGroupCommand $command): Group
    {
        $this->logger?->info('Creating group: ' . $command->name);
        $this->logger?->debug('Creating group details', [
            'name' => $command->name,
            'description' => $command->description,
            'type' => $command->type,
        ]);

        $group = match ($command->type) {
            'developer' => new \App\Entity\DeveloperGroup(),
            'designer' => new \App\Entity\DesignerGroup(),
            'tester' => new \App\Entity\TesterGroup(),
            default => new Group(),
        };
        $group->setName($command->name);
        $group->setDescription($command->description);
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->logger?->debug('Group entity created, preparing for persistence');

        $this->em->persist($group);
        $this->logger?->debug('Group persisted to entity manager');

        $this->em->flush();
        $this->logger?->info('Group created: ' . $group->getId());
        $this->logger?->debug('Group creation completed', [
            'groupId' => $group->getId(),
            'name' => $group->getName(),
            'createdAt' => $group->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $group;
    }
}
