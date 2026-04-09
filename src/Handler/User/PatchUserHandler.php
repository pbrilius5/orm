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
                $groupClass = match ($command->workGroup) {
                    'developer' => \App\Entity\DeveloperGroup::class,
                    'designer' => \App\Entity\DesignerGroup::class,
                    'tester' => \App\Entity\TesterGroup::class,
                    default => null,
                };
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

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->logger?->debug('Setting updated timestamp');

        $this->em->flush();
        $this->logger?->info('User patched: ' . $command->id);
        $this->logger?->debug('User patch completed', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'updatedAt' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $user;
    }
}
