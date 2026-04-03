<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\UserRole;
use App\Repository\UserRepository;
use Oryx\ORM\EntityManager;

class UserController
{
    private EntityManagerInterface $em;
    private UserRepository $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = new UserRepository($em);
    }

    public function index(): array
    {
        $users = $this->repository->findAll();
        return ['users' => $users];
    }

    public function show(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function create(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'] ?? '', PASSWORD_BCRYPT));
        $user->setCreatedAt(new \DateTimeImmutable());

        $roleNames = $data['roles'] ?? [Role::USER];
        $uniqueRoleNames = array_unique($roleNames);
        foreach ($uniqueRoleNames as $roleName) {
            $existingRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
            if ($existingRole) {
                $role = $existingRole;
            } else {
                $role = new Role();
                $role->setName($roleName);
                $this->em->persist($role);
            }

            $hasRole = false;
            foreach ($user->getUserRoles() as $existingUserRole) {
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

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(int $id, array $data): ?User
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
        if (isset($data['roles'])) {
            $requestRoleNames = $data['roles'] ?? [Role::USER];
            $uniqueRequestRoleNames = array_unique($requestRoleNames);

            $currentUserRoles = $user->getUserRoles()->toArray();

            foreach ($uniqueRequestRoleNames as $roleName) {
                $existingRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
                if ($existingRole) {
                    $role = $existingRole;
                } else {
                    $role = new Role();
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
                $shouldKeep = false;
                foreach ($uniqueRequestRoleNames as $requestRoleName) {
                    $requestRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $requestRoleName]);
                    if (!$requestRole) {
                        $requestRole = new Role();
                        $requestRole->setName($requestRoleName);
                    }

                    if ($existingUserRole->getRole() === $requestRole) {
                        $shouldKeep = true;
                        break;
                    }
                }

                if (!$shouldKeep) {
                    $user->getUserRoles()->removeElement($existingUserRole);
                    $existingUserRole->getRole()->removeUserRole($existingUserRole);
                }
            }
        }
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $user;
    }

    public function delete(int $id): bool
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
