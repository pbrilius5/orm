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

        $user->setEmail($command->email);
        $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));

        if ($command->groupId !== null) {
            $this->logger?->debug('Processing group change', [
                'newGroupId' => $command->groupId,
            ]);

            $currentGroups = $user->getAllGroups();
            $this->logger?->debug('Removing existing groups', [
                'groupsCount' => count($currentGroups),
            ]);

            foreach ($currentGroups as $existingGroup) {
                $user->removeGroup($existingGroup);
            }

            if ($command->groupId) {
                $groupRepo = $this->em->getRepository(\App\Entity\Group::class);
                $group = $groupRepo->find($command->groupId);
                if ($group && $group->isWorkGroup()) {
                    $user->addGroup($group);
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
        } else {
            $this->logger?->debug('No group change requested');
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
