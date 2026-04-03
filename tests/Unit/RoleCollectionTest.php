<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\UserRole;
use PHPUnit\Framework\TestCase;

class RoleCollectionTest extends TestCase
{
    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->setName($name);
        return $role;
    }

    public function testRoleUserRolesCollectionIsEmptyInitially(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $this->assertTrue($role->getUserRoles()->isEmpty());
        $this->assertCount(0, $role->getUserRoles());
    }

    public function testRoleAddUserRoleIncreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $role->addUserRole($userRole);

        $this->assertCount(1, $role->getUserRoles());
        $this->assertTrue($role->getUserRoles()->contains($userRole));
    }

    public function testRoleRemoveUserRoleDecreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $role->addUserRole($userRole);
        $this->assertCount(1, $role->getUserRoles());

        $role->removeUserRole($userRole);

        $this->assertCount(0, $role->getUserRoles());
    }

    public function testRoleIsGlobal(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $this->assertTrue($role->isGlobal());
    }
}
