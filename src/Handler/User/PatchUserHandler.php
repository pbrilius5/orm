<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\PatchUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class PatchUserHandler
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

    public function handle(PatchUserCommand $command): ?User
    {
        $this->logger?->info('Patching user: ' . $command->id);

        $user = $this->repository->find($command->id);
        if (!$user) {
            $this->logger?->warning('User not found: ' . $command->id);
            return null;
        }

        if ($command->email !== null) {
            $user->setEmail($command->email);
        }
        if ($command->password !== null) {
            $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));
        }
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

        $this->logger?->info('User patched: ' . $command->id);

        return $user;
    }
}
