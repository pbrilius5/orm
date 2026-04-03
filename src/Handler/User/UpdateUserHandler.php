<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\UpdateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class UpdateUserHandler
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

    public function handle(UpdateUserCommand $command): ?User
    {
        $this->logger?->info('Updating user: ' . $command->id);

        $user = $this->repository->find($command->id);
        if (!$user) {
            $this->logger?->warning('User not found: ' . $command->id);
            return null;
        }

        $user->setEmail($command->email);
        $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));

        if ($command->groupId !== null) {
            if ($command->groupId) {
                $groupRepo = $this->em->getRepository(\App\Entity\Group::class);
                $group = $groupRepo->find($command->groupId);
                $user->setGroup($group);
            } else {
                $user->setGroup(null);
            }
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->logger?->info('User updated: ' . $command->id);

        return $user;
    }
}
