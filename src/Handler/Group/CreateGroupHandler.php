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

        $group = new Group();
        $group->setName($command->name);
        $group->setDescription($command->description);
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($group);
        $this->em->flush();

        $this->logger?->info('Group created: ' . $group->getId());

        return $group;
    }
}
