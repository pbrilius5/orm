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
    private \App\Service\WorkGroupMapInterface $workGroupMap;

    public function __construct(EntityManager $em, \App\Service\WorkGroupMapInterface $workGroupMap, ?LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->repository = new UserRepository($em);
        $this->logger = $logger;
        $this->workGroupMap = $workGroupMap;
    }

    public function handle(PatchUserCommand $command): ?User
    {
        $this->logger?->info('Patching user: ' . $command->id);
        $this->logger?->debug('Patching user details', [
            'userId' => $command->id,
            'email' => $command->email,
            'hasPassword' => !is_null($command->password),
            'workGroup' => $command->workGroup,
        ]);

        $user = $this->repository->find($command->id);
        if (!$user) {
            $this->logger?->warning('User not found for patch', [
                'userId' => $command->id,
            ]);
            return null;
        }

        $this->logger?->debug('Found user for patch', [
            'userId' => $user->getId(),
            'currentEmail' => $user->getEmail(),
        ]);

        if ($command->email !== null) {
            $this->logger?->debug('Updating email');
            $user->setEmail($command->email);
        }
        if ($command->password !== null) {
            $this->logger?->debug('Updating password');
            $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));
        }
        if ($command->workGroup !== null) {
            $this->logger?->debug('Processing work group change', [
                'newWorkGroup' => $command->workGroup,
            ]);

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
        } else {
            $this->logger?->debug('No work group change requested');
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
        $this->logger?->info('User patched: ' . $command->id);
        $this->logger?->debug('User patch completed', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'updatedAt' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $this->repository->findForApi($command->id);
    }
}
