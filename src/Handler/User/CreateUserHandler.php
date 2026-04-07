<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class CreateUserHandler
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

    public function handle(CreateUserCommand $command): User
    {
        $this->logger?->info('Creating user: ' . $command->email);

        $existingUser = $this->em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => $command->email]);
        if ($existingUser) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User();
        $user->setEmail($command->email);
        $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));
        $user->setCreatedAt(new \DateTimeImmutable());

        if ($command->groupId) {
            $groupRepo = $this->em->getRepository(\App\Entity\Group::class);
            $group = $groupRepo->find($command->groupId);
            if ($group && $group->isWorkGroup()) {
                $userGroup = new \App\Entity\UserGroup();
                $userGroup->setUser($user);
                $userGroup->setGroup($group);
                $userGroup->setGrantedAt(new \DateTimeImmutable());
                $this->em->persist($userGroup);
            }
        }

        $baseRole = $this->em->getRepository(\App\Entity\Role::class)->findOneBy(['name' => \App\Entity\Role::USER]);
        if (!$baseRole) {
            $baseRole = new \App\Entity\Role();
            $baseRole->setName(\App\Entity\Role::USER);
            $this->em->persist($baseRole);
        }

        $userRole = new \App\Entity\UserRole();
        $userRole->setUser($user);
        $userRole->setRole($baseRole);
        $this->em->persist($userRole);

        $this->em->persist($user);
        $this->em->flush();

        $this->logger?->info('User created: ' . $user->getId());

        return $user;
    }
}
