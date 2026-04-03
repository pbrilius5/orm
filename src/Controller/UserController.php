<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArchitectRole;
use App\Entity\GameMasterRole;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\WizardRole;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;

class UserController
{
    private EntityManager $em;
    private UserRepository $repository;

    private const GAMIFICATION_ROLE_CLASSES = [
        WizardRole::NAME => WizardRole::class,
        ArchitectRole::NAME => ArchitectRole::class,
        GameMasterRole::NAME => GameMasterRole::class,
    ];

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repository = new UserRepository($em);
    }

    public function index(): array
    {
        $users = $this->repository->findAll();
        return ['users' => $users];
    }

    public function show(string $id): ?User
    {
        return $this->repository->find($id);
    }

    public function create(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'] ?? '', PASSWORD_BCRYPT));
        $user->setCreatedAt(new \DateTimeImmutable());

        $baseRole = $this->em->getRepository(Role::class)->findOneBy(['name' => Role::USER]);
        if (!$baseRole) {
            $baseRole = new Role();
            $baseRole->setName(Role::USER);
            $this->em->persist($baseRole);
        }

        $userRole = new UserRole();
        $userRole->setUser($user);
        $userRole->setRole($baseRole);
        $this->em->persist($userRole);
        $user->getUserRoles()->add($userRole);
        $baseRole->getUserRoles()->add($userRole);

        $gamificationRoleNames = isset($data['gamification_roles'])
            ? (is_array($data['gamification_roles']) ? $data['gamification_roles'] : [$data['gamification_roles']])
            : [];

        foreach ($gamificationRoleNames as $roleName) {
            if (!isset(self::GAMIFICATION_ROLE_CLASSES[$roleName])) {
                continue;
            }

            $roleClass = self::GAMIFICATION_ROLE_CLASSES[$roleName];
            $existingRole = $this->em->getRepository($roleClass)->findOneBy(['name' => $roleName]);
            if ($existingRole) {
                $role = $existingRole;
            } else {
                $role = new $roleClass();
                $role->setName($roleName);
                $this->em->persist($role);
            }

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);
            $this->em->persist($userRole);
            $user->getUserRoles()->add($userRole);
            $role->getUserRoles()->add($userRole);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(string $id, array $data): ?User
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return null;
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        }

        $requestRoleNames = isset($data['gamification_roles'])
            ? (is_array($data['gamification_roles']) ? $data['gamification_roles'] : [$data['gamification_roles']])
            : [];

        $currentUserRoles = $user->getUserRoles()->toArray();

        foreach ($requestRoleNames as $roleName) {
            if (!isset(self::GAMIFICATION_ROLE_CLASSES[$roleName])) {
                continue;
            }

            $roleClass = self::GAMIFICATION_ROLE_CLASSES[$roleName];
            $existingRole = $this->em->getRepository($roleClass)->findOneBy(['name' => $roleName]);
            if ($existingRole) {
                $role = $existingRole;
            } else {
                $role = new $roleClass();
                $role->setName($roleName);
                $this->em->persist($role);
            }

            $hasRole = false;
            foreach ($currentUserRoles as $existingUserRole) {
                if ($existingUserRole->getRole() === $role) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($role);
                $this->em->persist($userRole);
                $user->getUserRoles()->add($userRole);
                $role->getUserRoles()->add($userRole);
            }
        }

        foreach ($currentUserRoles as $existingUserRole) {
            $existingRole = $existingUserRole->getRole();
            if ($existingRole->getName() === Role::USER) {
                continue;
            }

            $shouldKeep = in_array($existingRole->getName(), $requestRoleNames, true);

            if (!$shouldKeep) {
                $user->getUserRoles()->removeElement($existingUserRole);
                $existingRole->removeUserRole($existingUserRole);
            }
        }
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $user;
    }

    public function delete(string $id): bool
    {
        $user = $this->repository->find($id);

        if (!$user) {
            return false;
        }

        $this->em->remove($user);
        $this->em->flush();

        return true;
    }
}
