<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Team;
use App\Entity\Role;
use App\Entity\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

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

        // Ensure user has a team before setting roles to satisfy NOT NULL constraint
        if ($user->getTeam() === null) {
            $team = new Team();
            $team->setName('Default Team');
            $team->setCreatedAt(new \DateTimeImmutable());
            $user->setTeam($team);
            $this->em->persist($team);
        } else {
            $team = $user->getTeam();
        }

        // Process roles - look up existing roles or create new ones
        // Remove duplicates to prevent unique constraint violations
        $roleNames = $data['roles'] ?? [Role::USER];
        $uniqueRoleNames = array_unique($roleNames);
        foreach ($uniqueRoleNames as $roleName) {
            // Try to find existing role by name
            $existingRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
            if ($existingRole) {
                $role = $existingRole;
            } else {
                // Create new role if it doesn't exist
                $role = new Role();
                $role->setName($roleName);
                $this->em->persist($role);
            }

            // Check if this user already has this role for this team to prevent duplicates
            // (For new user, this will always be false, but checking for consistency)
            $hasRole = false;
            foreach ($user->getUserRoles() as $existingUserRole) {
                if ($existingUserRole->getRole() === $role && $existingUserRole->getTeam() === $team) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                // Create and persist UserRole entity
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($role);
                $userRole->setTeam($team);
                $this->em->persist($userRole);

                // Add to collections (both sides of the bidirectional relationships)
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
            // Get or create the team if needed
            $team = $user->getTeam();
            if ($team === null) {
                $team = new Team();
                $team->setName('Default Team');
                $team->setCreatedAt(new \DateTimeImmutable());
                $user->setTeam($team);
                $this->em->persist($team);
            }

            // Process roles - handle additions and removals properly
            // Remove duplicates to prevent unique constraint violations
            // Ensure ROLE_USER is always present (cannot be removed)
            $requestRoleNames = $data['roles'] ?? [Role::USER];
            $uniqueRequestRoleNames = array_unique($requestRoleNames);

            // Get current roles for comparison
            $currentUserRoles = $user->getUserRoles()->toArray();

            // Process each role in the request
            foreach ($uniqueRequestRoleNames as $roleName) {
                // Try to find existing role by name
                $existingRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
                if ($existingRole) {
                    $role = $existingRole;
                } else {
                    // Create new role if it doesn't exist
                    $role = new Role();
                    $role->setName($roleName);
                    $this->em->persist($role);
                }

                // Check if this user already has this role for this team
                $hasRole = false;
                foreach ($currentUserRoles as $existingUserRole) {
                    if ($existingUserRole->getRole() === $role && $existingUserRole->getTeam() === $team) {
                        $hasRole = true;
                        break;
                    }
                }

                if (!$hasRole) {
                    // Create and persist UserRole entity (role needs to be added)
                    $userRole = new UserRole();
                    $userRole->setUser($user);
                    $userRole->setRole($role);
                    $userRole->setTeam($team);
                    $this->em->persist($userRole);

                    // Add to collections (both sides of the bidirectional relationships)
                    $user->getUserRoles()->add($userRole);
                    $role->getUserRoles()->add($userRole);
                }
            }

            // Remove roles that are in current roles but not in request
            foreach ($currentUserRoles as $existingUserRole) {
                $shouldKeep = false;
                foreach ($uniqueRequestRoleNames as $requestRoleName) {
                    $requestRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $requestRoleName]);
                    if (!$requestRole) {
                        // Create the role if it doesn't exist (shouldn't happen in practice, but safe)
                        $requestRole = new Role();
                        $requestRole->setName($requestRoleName);
                    }

                    if ($existingUserRole->getRole() === $requestRole && $existingUserRole->getTeam() === $team) {
                        $shouldKeep = true;
                        break;
                    }
                }

                if (!$shouldKeep) {
                    // Remove the UserRole entity (role needs to be removed)
                    $user->getUserRoles()->removeElement($existingUserRole);
                    $existingUserRole->getRole()->removeUserRole($existingUserRole);
                    // Note: We don't call $this->em->remove($existingUserRole) here because
                    // the flush() at the end will handle it based on the collection changes
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
