<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\ListUsersCommand;
use App\DTO\UserApiDTO;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class ListUsersHandler
{
    private EntityManager $em;
    private UserRepository $repository;
    private ?LoggerInterface $logger;

    public function __construct(EntityManager $em, ?LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->repository = new UserRepository($em);
        $this->logger = $logger;
    }

    public function handle(ListUsersCommand $command): array
    {
        $this->logger?->debug('Listing users for API');

        if ($command->search) {
            return $this->repository->findAllWithFilterForApi($command->search);
        }

        return $this->repository->findAllForApi();
    }
}
