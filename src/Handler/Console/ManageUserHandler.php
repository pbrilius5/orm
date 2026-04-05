<?php

declare(strict_types=1);

namespace App\Handler\Console;

use App\Command\Console\ManageUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class ManageUserHandler
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

    public function handle(ManageUserCommand $command): array
    {
        $this->logger?->info('Managing user: ' . $command->id . ' action: ' . $command->action);

        $user = $this->repository->find($command->id);
        if (!$user) {
            $this->logger?->warning('User not found: ' . $command->id);
            return ['error' => 'User not found'];
        }

        $result = match ($command->action) {
            ManageUserCommand::ACTION_SHOW => $this->showUser($user),
            ManageUserCommand::ACTION_ENABLE => $this->enableUser($user),
            ManageUserCommand::ACTION_DISABLE => $this->disableUser($user),
            ManageUserCommand::ACTION_ASSIGN_ROLE => $this->assignRole($user, $command->role),
            ManageUserCommand::ACTION_REMOVE_ROLE => $this->removeRole($user, $command->role),
            ManageUserCommand::ACTION_CHANGE_GROUP => $this->changeGroup($user, $command->groupId),
            default => ['error' => 'Unknown action: ' . $command->action],
        };

        return $result;
    }

    private function showUser(User $user): array
    {
        return [
            'id' => $user->getId()?->toString(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'groups' => $user->getGroups(),
        ];
    }

    private function enableUser(User $user): array
    {
        $this->logger?->info('User enabled: ' . $user->getId());
        return ['message' => 'User enabled', 'id' => $user->getId()?->toString()];
    }

    private function disableUser(User $user): array
    {
        $this->logger?->info('User disabled: ' . $user->getId());
        return ['message' => 'User disabled', 'id' => $user->getId()?->toString()];
    }

    private function assignRole(User $user, ?string $role): array
    {
        if (!$role) {
            return ['error' => 'Role is required'];
        }

        $roleEntity = new \App\Entity\Role();
        $roleEntity->setName($role);
        $this->em->persist($roleEntity);

        $userRole = new \App\Entity\UserRole();
        $userRole->setUser($user);
        $userRole->setRole($roleEntity);
        $this->em->persist($userRole);

        $this->em->flush();

        $this->logger?->info('Role assigned: ' . $role . ' to user: ' . $user->getId());

        return ['message' => 'Role assigned', 'role' => $role];
    }

    private function removeRole(User $user, ?string $role): array
    {
        if (!$role) {
            return ['error' => 'Role is required'];
        }

        $this->logger?->info('Role removed: ' . $role . ' from user: ' . $user->getId());

        return ['message' => 'Role removed', 'role' => $role];
    }

    private function changeGroup(User $user, ?string $groupId): array
    {
        if (!$groupId) {
            foreach ($user->getAllGroups() as $existingGroup) {
                $user->removeGroup($existingGroup);
            }
            $this->em->flush();
            return ['message' => 'Group removed'];
        }

        $group = $this->em->getRepository(\App\Entity\Group::class)->find($groupId);
        if (!$group) {
            return ['error' => 'Group not found'];
        }

        $user->addGroup($group);
        $this->em->flush();

        $this->logger?->info('Group changed to: ' . $groupId . ' for user: ' . $user->getId());

        return ['message' => 'Group changed', 'group' => $groupId];
    }
}
