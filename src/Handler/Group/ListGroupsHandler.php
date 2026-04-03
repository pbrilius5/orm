<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\ListGroupsCommand;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class ListGroupsHandler
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

    public function handle(ListGroupsCommand $command): array
    {
        $this->logger?->debug('Listing groups for API');

        if ($command->search) {
            return $this->repository->findAllWithFilterForApi($command->search);
        }

        return $this->repository->findAllForApi();
    }
}
