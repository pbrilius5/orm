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
    private \App\Service\WorkGroupMapInterface $workGroupMap;

    public function __construct(EntityManager $em, \App\Service\WorkGroupMapInterface $workGroupMap, ?LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->repository = new UserRepository($em);
        $this->logger = $logger;
        $this->workGroupMap = $workGroupMap;
    }

    public function handle(UpdateUserCommand $command): ?User
    {
        $this->logger?->info('Updating user: ' . $command->id);
        $this->logger?->debug('Updating user details', [
            'userId' => $command->id,
            'email' => $command->email,
            'hasPassword' => !empty($command->password),
            'workGroup' => $command->workGroup,
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

        if ($command->email !== null) {
            $user->setEmail($command->email);
        }
        if ($command->password !== null) {
            $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));
        }

        if ($command->workGroup !== null) {
            $this->logger?->debug('Processing work group change', [
                'newWorkGroup' => $command->workGroup,
            ]);

            $currentWorkGroup = $user->getWorkGroup();
            $currentDiscriminator = $currentWorkGroup ? $this->getGroupDiscriminator($currentWorkGroup) : null;

            if ($currentDiscriminator === $command->workGroup) {
                $this->logger?->debug('Work group unchanged, skipping');
            } else {
                $currentGroups = $user->getAllGroups();
                foreach ($currentGroups as $existingGroup) {
                    foreach ($user->getUserGroups() as $userGroup) {
                        if ($userGroup->getGroup() === $existingGroup) {
                            $this->em->remove($userGroup);
                            break;
                        }
                    }
                    $user->removeGroup($existingGroup);
                }

                if ($command->workGroup) {
                    $groupClass = $this->workGroupMap->getFqcnForDiscriminator($command->workGroup);
                    if ($groupClass) {
                        $group = $this->em->getRepository($groupClass)->findOneBy([]);
                        if ($group) {
                            $userGroup = $user->addGroup($group);
                            $this->em->persist($userGroup);
                            $this->logger?->debug('Added user to work group', [
                                'groupName' => $group->getName(),
                            ]);
                        }
                    }
                } else {
                    $this->logger?->debug('Setting user to no work group');
                }
            }
        } else {
            $this->logger?->debug('No group change requested');
        }

        if (!empty($command->roles)) {
            $this->logger?->debug('Processing gamification role update', [
                'newRoles' => $command->roles,
            ]);

            // current active gamification role names
            $currentGamification = $user->getGamificationRoleNames();

            // determine which to remove (present now but not requested)
            $toRemove = array_diff($currentGamification, $command->roles);

            foreach ($user->getUserRoles() as $userRole) {
                $role = $userRole->getRole();
                if ($role->isGamificationRole() && in_array($role->getName(), $toRemove, true)) {
                    $this->logger?->debug('Removing existing gamification role', [
                        'roleName' => $role->getName(),
                    ]);
                    $this->em->remove($userRole);
                }
            }

            // Add requested roles that the user does not already have
            foreach ($command->roles as $roleName) {
                if ($user->hasGamificationRole($roleName)) {
                    // already has this active role; skip
                    continue;
                }

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
                    // Extra safety: check DB for existing mapping to avoid duplicates
                    $existing = $this->em->getRepository(\App\Entity\UserRole::class)
                        ->findOneBy(['user' => $user, 'role' => $role]);
                    if ($existing) {
                        continue;
                    }

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

        return $this->repository->findForApi($command->id);
    }

    private function getGroupDiscriminator(\App\Entity\Group $group): string
    {
        return $this->workGroupMap->getDiscriminatorForGroup($group);
    }
}
