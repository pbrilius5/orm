<?php

declare(strict_types=1);

namespace App\Handler\Group;

use App\Command\Group\GetGroupCommand;
use App\DTO\GroupApiDTO;
use App\Repository\GroupRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class GetGroupHandler
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

    public function handle(GetGroupCommand $command): ?GroupApiDTO
    {
        $this->logger?->debug('Getting group for API: ' . $command->id);

        return $this->repository->findForApi($command->id);
    }
}
