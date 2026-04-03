<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\DeleteUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class DeleteUserHandler
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

    public function handle(DeleteUserCommand $command): bool
    {
        $this->logger?->info('Deleting user: ' . $command->id);

        $user = $this->repository->find($command->id);
        if (!$user) {
            $this->logger?->warning('User not found: ' . $command->id);
            return false;
        }

        $this->em->remove($user);
        $this->em->flush();

        $this->logger?->info('User deleted: ' . $command->id);

        return true;
    }
}
