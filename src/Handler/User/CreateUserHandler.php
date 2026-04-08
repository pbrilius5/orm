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
        $this->logger?->debug('Creating user details', [
            'email' => $command->email,
            'hasPassword' => !empty($command->password),
            'groupId' => $command->groupId,
            'rolesCount' => is_array($command->roles) ? count($command->roles) : 0,
        ]);

        $existingUser = $this->em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => $command->email]);
        if ($existingUser) {
            $this->logger?->warning('User creation failed - email already exists', [
                'email' => $command->email,
            ]);
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User();
        $user->setEmail($command->email);
        $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));
        $user->setCreatedAt(new \DateTimeImmutable());

        if ($command->groupId) {
            $this->logger?->debug('Processing group association', [
                'groupId' => $command->groupId,
            ]);
            $groupRepo = $this->em->getRepository(\App\Entity\Group::class);
            $group = $groupRepo->find($command->groupId);
            if ($group && $group->isWorkGroup()) {
                $userGroup = new \App\Entity\UserGroup();
                $userGroup->setUser($user);
                $userGroup->setGroup($group);
                $userGroup->setGrantedAt(new \DateTimeImmutable());
                $this->em->persist($userGroup);
                $this->logger?->debug('Group association successful');
            } else {
                $this->logger?->warning('Group not found or not a work group', [
                    'groupId' => $command->groupId,
                ]);
            }
        }

        $baseRole = $this->em->getRepository(\App\Entity\Role::class)->findOneBy(['name' => \App\Entity\Role::USER]);
        if (!$baseRole) {
            $this->logger?->debug('Base user role not found, creating new');
            $baseRole = new \App\Entity\Role();
            $baseRole->setName(\App\Entity\Role::USER);
            $this->em->persist($baseRole);
        } else {
            $this->logger?->debug('Base user role found');
        }

        if (!empty($command->roles)) {
            $this->logger?->debug('Processing gamification roles', [
                'roles' => $command->roles,
            ]);
            foreach ($command->roles as $roleName) {
                $role = $this->em->getRepository(\App\Entity\Role::class)->findOneBy(['name' => $roleName]);
                if (!$role) {
                    $roleClass = match ($roleName) {
                        'ROLE_WIZARD' => \App\Entity\WizardRole::class,
                        'ROLE_ARCHITECT' => \App\Entity\ArchitectRole::class,
                        'ROLE_GAME_MASTER' => \App\Entity\GameMasterRole::class,
                        default => null,
                    };
                    if ($roleClass) {
                        $role = new $roleClass();
                        $role->setName($roleName);
                        $this->em->persist($role);
                        $this->logger?->debug('Created new gamification role', [
                            'roleName' => $roleName,
                        ]);
                    }
                }
                if ($role) {
                    $userRole = new \App\Entity\UserRole();
                    $userRole->setUser($user);
                    $userRole->setRole($role);
                    $this->em->persist($userRole);
                    $this->logger?->debug('Added gamification role to user', [
                        'roleName' => $roleName,
                    ]);
                }
            }
        }

        $userRole = new \App\Entity\UserRole();
        $userRole->setUser($user);
        $userRole->setRole($baseRole);
        $this->em->persist($userRole);
        $this->logger?->debug('User role association created');

        $this->em->persist($user);
        $this->logger?->debug('User entity prepared for persistence');

        $this->em->flush();
        $this->logger?->info('User created: ' . $user->getId());
        $this->logger?->debug('User creation completed', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $user;
    }
}
