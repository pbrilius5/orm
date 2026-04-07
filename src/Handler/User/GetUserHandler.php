<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\GetUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class GetUserHandler
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

    public function handle(GetUserCommand $command): ?User
    {
        $this->logger?->debug('Getting user for API: ' . $command->id);

        return $this->repository->findForApi($command->id);
    }
}
