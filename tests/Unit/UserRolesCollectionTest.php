<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class UserRolesCollectionTest extends TestCase
{
    public function testGetAllRolesReturnsOnlyActive(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $architectRole = new Role();
        $architectRole->setName(Role::ARCHITECT);

        $expiredUserRole = new UserRole();
        $expiredUserRole->setRole($wizardRole);
        $expiredUserRole->setGrantedAt(new \DateTimeImmutable('-2 days'));
        $expiredUserRole->setExpiresAt(new \DateTimeImmutable('-1 day'));

        $activeUserRole = new UserRole();
        $activeUserRole->setRole($architectRole);
        $activeUserRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($expiredUserRole);
        $user->getUserRoles()->add($activeUserRole);

        $activeRoles = $user->getAllRoles();

        $this->assertCount(1, $activeRoles);
        $this->assertSame(Role::ARCHITECT, $activeRoles[0]->getName());
    }

    public function testGetRoleNamesUsesCollectionMap(): void
    {
        $user = new User();
        $user->setEmail('mapper@example.com');
        $user->setPassword('password123');

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $gameMasterRole = new Role();
        $gameMasterRole->setName(Role::GAME_MASTER);

        $wizardUserRole = new UserRole();
        $wizardUserRole->setRole($wizardRole);
        $wizardUserRole->setGrantedAt(new \DateTimeImmutable());

        $gmUserRole = new UserRole();
        $gmUserRole->setRole($gameMasterRole);
        $gmUserRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($wizardUserRole);
        $user->getUserRoles()->add($gmUserRole);

        $roleNames = $user->getRoleNames();

        $this->assertCount(2, $roleNames);
        $this->assertContains(Role::WIZARD, $roleNames);
        $this->assertContains(Role::GAME_MASTER, $roleNames);
    }

    public function testCollectionCountReturnsCorrectNumber(): void
    {
        $user = new User();
        $user->setEmail('count@example.com');
        $user->setPassword('password123');

        $this->assertInstanceOf(Collection::class, $user->getUserRoles());
        $this->assertCount(0, $user->getUserRoles());

        $role = new Role();
        $role->setName(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRole);

        $this->assertCount(1, $user->getUserRoles());
    }

    public function testHasRoleUsesCollectionFilter(): void
    {
        $user = new User();
        $user->setEmail('filter@example.com');
        $user->setPassword('password123');

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($wizardRole);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRole);

        $this->assertTrue($user->hasRole(Role::WIZARD));
        $this->assertFalse($user->hasRole(Role::ARCHITECT));
    }

    public function testCollectionFirstReturnsFirstMatchingElement(): void
    {
        $user = new User();
        $user->setEmail('first@example.com');
        $user->setPassword('password123');

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $architectRole = new Role();
        $architectRole->setName(Role::ARCHITECT);

        $wizardUserRole = new UserRole();
        $wizardUserRole->setRole($wizardRole);
        $wizardUserRole->setGrantedAt(new \DateTimeImmutable());

        $architectUserRole = new UserRole();
        $architectUserRole->setRole($architectRole);
        $architectUserRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($wizardUserRole);
        $user->getUserRoles()->add($architectUserRole);

        $firstArchitect = $user->getUserRoles()
            ->filter(fn($ur) => $ur->getRole()->getName() === Role::ARCHITECT)
            ->first();

        $this->assertNotFalse($firstArchitect);
        $this->assertSame(Role::ARCHITECT, $firstArchitect->getRole()->getName());
    }

    public function testCollectionExistsReturnsTrueForMatchingElement(): void
    {
        $user = new User();
        $user->setEmail('exists@example.com');
        $user->setPassword('password123');

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($wizardRole);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRole);

        $hasWizard = $user->getUserRoles()
            ->exists(fn($key, $ur) => $ur->getRole()->getName() === Role::WIZARD);

        $this->assertTrue($hasWizard);
    }

    public function testAddRoleDoesNotDuplicate(): void
    {
        $user = new User();
        $user->setEmail('add-role@example.com');
        $user->setPassword('password123');

        $role = new Role();
        $role->setName(Role::WIZARD);

        $user->addRole($role);
        $user->addRole($role);

        $this->assertCount(1, $user->getUserRoles());
    }

    public function testRemoveRoleRemovesFromCollection(): void
    {
        $user = new User();
        $user->setEmail('remove-role@example.com');
        $user->setPassword('password123');

        $role = new Role();
        $role->setName(Role::WIZARD);

        $user->addRole($role);
        $this->assertCount(1, $user->getUserRoles());

        $user->removeRole($role);
        $this->assertCount(0, $user->getUserRoles());
    }

    public function testGetRolesReturnsActiveRoleNames(): void
    {
        $user = new User();
        $user->setEmail('get-roles@example.com');
        $user->setPassword('password123');

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $architectRole = new Role();
        $architectRole->setName(Role::ARCHITECT);

        $user->addRole($wizardRole);
        $user->addRole($architectRole);

        $roles = $user->getRoles();

        $this->assertCount(2, $roles);
        $this->assertContains(Role::WIZARD, $roles);
        $this->assertContains(Role::ARCHITECT, $roles);
    }
}
