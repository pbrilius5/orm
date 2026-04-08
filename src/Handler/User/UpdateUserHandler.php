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
        $this->logger?->debug('Updating user details', [
            'userId' => $command->id,
            'email' => $command->email,
            'hasPassword' => !empty($command->password),
            'groupId' => $command->groupId,
            'roles' => $command->roles ?? [],
        ]);

        $user = $this->repository->find($command->id);
        if (!$user) {
            $this->logger?->warning('User not found for update', [
                'userId' => $command->id,
            ]);
            return null;
        }

        $this->logger?->debug('Found user for update', [
            'userId' => $user->getId(),
            'currentEmail' => $user->getEmail(),
        ]);

        $existingUser = $this->em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => $command->email]);
        if ($existingUser && (string) $existingUser->getId() !== (string) $command->id) {
            $this->logger?->warning('Update failed - email already in use', [
                'email' => $command->email,
                'existingUserId' => (string) $existingUser->getId(),
            ]);
            throw new \InvalidArgumentException('Email already in use by another user');
        }

        $user->setEmail($command->email);
        $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));

        if ($command->groupId !== null) {
            $this->logger?->debug('Processing group change', [
                'newGroupId' => $command->groupId,
            ]);

            $currentWorkGroup = $user->getWorkGroup();
            if ($currentWorkGroup && (string) $currentWorkGroup->getId() === $command->groupId) {
                $this->logger?->debug('Group unchanged, skipping', [
                    'currentGroupId' => (string) $currentWorkGroup->getId(),
                ]);
            } else {
                $currentGroups = $user->getAllGroups();
                $this->logger?->debug('Removing existing groups', [
                    'groupsCount' => count($currentGroups),
                ]);

                foreach ($currentGroups as $existingGroup) {
                    $this->logger?->debug('Removing group', [
                        'groupName' => $existingGroup->getName(),
                        'groupId' => $existingGroup->getId(),
                    ]);
                    foreach ($user->getUserGroups() as $userGroup) {
                        if ($userGroup->getGroup() === $existingGroup) {
                            $this->logger?->debug('Removing UserGroup from DB', [
                                'userGroupId' => $userGroup->getId(),
                            ]);
                            $this->em->remove($userGroup);
                            break;
                        }
                    }
                    $user->removeGroup($existingGroup);
                }

                if ($command->groupId) {
                    $groupRepo = $this->em->getRepository(\App\Entity\Group::class);
                    $group = $groupRepo->find($command->groupId);
                    if ($group && $group->isWorkGroup()) {
                        $userGroup = $user->addGroup($group);
                        $this->em->persist($userGroup);
                        $this->logger?->debug('Added user to group', [
                            'groupId' => $group->getId(),
                            'groupName' => $group->getName(),
                        ]);
                    } else {
                        $this->logger?->warning('Group not found or not a work group', [
                            'groupId' => $command->groupId,
                        ]);
                    }
                } else {
                    $this->logger?->debug('Setting user to no group');
                }
            }
        } else {
            $this->logger?->debug('No group change requested');
        }

        if (!empty($command->roles)) {
            $this->logger?->debug('Processing gamification role update', [
                'newRoles' => $command->roles,
            ]);
            foreach ($user->getUserRoles() as $userRole) {
                $role = $userRole->getRole();
                if ($role->isGamificationRole()) {
                    $this->logger?->debug('Removing existing gamification role', [
                        'roleName' => $role->getName(),
                    ]);
                    $this->em->remove($userRole);
                }
            }
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

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->logger?->debug('Setting updated timestamp');

        $this->em->flush();
        $this->logger?->info('User updated: ' . $command->id);
        $this->logger?->debug('User update completed', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'updatedAt' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $user;
    }
}
