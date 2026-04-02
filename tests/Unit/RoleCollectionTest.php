<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\Team;
use App\Entity\UserRole;
use App\Entity\Wand;
use App\Entity\Patronus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class RoleCollectionTest extends TestCase
{
    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->setName($name);
        return $role;
    }

    private function createTeam(string $name): Team
    {
        $team = new Team();
        $team->setName($name);
        $team->setCreatedAt(new \DateTimeImmutable());
        return $team;
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
        $team = $this->createTeam('Level Design');

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setTeam($team);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $role->addUserRole($userRole);

        $this->assertCount(1, $role->getUserRoles());
        $this->assertTrue($role->getUserRoles()->contains($userRole));
    }

    public function testRoleRemoveUserRoleDecreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);
        $team = $this->createTeam('Level Design');

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setTeam($team);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $role->addUserRole($userRole);
        $this->assertCount(1, $role->getUserRoles());

        $role->removeUserRole($userRole);

        $this->assertCount(0, $role->getUserRoles());
    }

    public function testRoleWandsCollectionIsEmptyInitially(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $this->assertTrue($role->getWands()->isEmpty());
    }

    public function testRoleAddWandIncreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $wand = new Wand();
        $wand->setRole($role);
        $wand->setName('Test Wand');
        $wand->setPermissions(json_encode(['read']));
        $wand->setCreatedAt(new \DateTimeImmutable());

        $role->addWand($wand);

        $this->assertCount(1, $role->getWands());
    }

    public function testRoleRemoveWandDecreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $wand = new Wand();
        $wand->setRole($role);
        $wand->setName('Test Wand');
        $wand->setPermissions(json_encode(['read']));
        $wand->setCreatedAt(new \DateTimeImmutable());

        $role->addWand($wand);
        $this->assertCount(1, $role->getWands());

        $role->removeWand($wand);

        $this->assertCount(0, $role->getWands());
    }

    public function testRolePatronusesCollectionIsEmptyInitially(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $this->assertTrue($role->getPatronuses()->isEmpty());
    }

    public function testRoleAddPatronusIncreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);
        $team = $this->createTeam('Level Design');

        $patronus = new Patronus();
        $patronus->setRole($role);
        $patronus->setTeam($team);
        $patronus->setIssuedAt(new \DateTimeImmutable());
        $patronus->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $role->addPatronus($patronus);

        $this->assertCount(1, $role->getPatronuses());
    }

    public function testRoleRemovePatronusDecreasesCount(): void
    {
        $role = $this->createRole(Role::WIZARD);
        $team = $this->createTeam('Level Design');

        $patronus = new Patronus();
        $patronus->setRole($role);
        $patronus->setTeam($team);
        $patronus->setIssuedAt(new \DateTimeImmutable());
        $patronus->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $role->addPatronus($patronus);
        $this->assertCount(1, $role->getPatronuses());

        $role->removePatronus($patronus);

        $this->assertCount(0, $role->getPatronuses());
    }

    public function testRoleFilterWandsByPermission(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $wand1 = new Wand();
        $wand1->setRole($role);
        $wand1->setName('Read Wand');
        $wand1->setPermissions(json_encode(['read']));
        $wand1->setCreatedAt(new \DateTimeImmutable());

        $wand2 = new Wand();
        $wand2->setRole($role);
        $wand2->setName('Write Wand');
        $wand2->setPermissions(json_encode(['read', 'write']));
        $wand2->setCreatedAt(new \DateTimeImmutable());

        $role->addWand($wand1);
        $role->addWand($wand2);

        $writeWands = array_values($role->getWands()
            ->filter(fn($wand) => in_array('write', json_decode($wand->getPermissions(), true)))
            ->toArray());

        $this->assertCount(1, $writeWands);
        $this->assertSame('Write Wand', $writeWands[0]->getName());
    }

    public function testRoleClearWands(): void
    {
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 5; $i++) {
            $wand = new Wand();
            $wand->setRole($role);
            $wand->setName("Wand {$i}");
            $wand->setPermissions(json_encode(['read']));
            $wand->setCreatedAt(new \DateTimeImmutable());
            $role->addWand($wand);
        }

        $this->assertCount(5, $role->getWands());

        $role->getWands()->clear();

        $this->assertCount(0, $role->getWands());
    }

    public function testRoleGetWandNames(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $wand1 = new Wand();
        $wand1->setRole($role);
        $wand1->setName('Wand of Power');
        $wand1->setPermissions(json_encode(['read']));
        $wand1->setCreatedAt(new \DateTimeImmutable());

        $wand2 = new Wand();
        $wand2->setRole($role);
        $wand2->setName('Wand of Protection');
        $wand2->setPermissions(json_encode(['read']));
        $wand2->setCreatedAt(new \DateTimeImmutable());

        $role->addWand($wand1);
        $role->addWand($wand2);

        $names = $role->getWands()
            ->map(fn($wand) => $wand->getName())
            ->toArray();

        $this->assertCount(2, $names);
        $this->assertContains('Wand of Power', $names);
        $this->assertContains('Wand of Protection', $names);
    }

    public function testRoleForAllWandsHavePermissions(): void
    {
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 3; $i++) {
            $wand = new Wand();
            $wand->setRole($role);
            $wand->setName("Wand {$i}");
            $wand->setPermissions(json_encode(['read']));
            $wand->setCreatedAt(new \DateTimeImmutable());
            $role->addWand($wand);
        }

        $allHavePerms = $role->getWands()
            ->forAll(fn($key, $wand) => !empty($wand->getPermissions()));

        $this->assertTrue($allHavePerms);
    }

    public function testRoleFirstWand(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $wand = new Wand();
        $wand->setRole($role);
        $wand->setName('First Wand');
        $wand->setPermissions(json_encode(['read']));
        $wand->setCreatedAt(new \DateTimeImmutable());

        $role->addWand($wand);

        $first = $role->getWands()->first();

        $this->assertNotFalse($first);
        $this->assertSame('First Wand', $first->getName());
    }

    public function testRoleFirstReturnsFalseWhenEmpty(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $this->assertFalse($role->getWands()->first());
    }

    public function testRoleSliceWands(): void
    {
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 10; $i++) {
            $wand = new Wand();
            $wand->setRole($role);
            $wand->setName("Wand {$i}");
            $wand->setPermissions(json_encode(['read']));
            $wand->setCreatedAt(new \DateTimeImmutable());
            $role->addWand($wand);
        }

        $sliced = $role->getWands()->slice(3, 4);

        $this->assertCount(4, $sliced);
    }

    public function testRolePartitionWands(): void
    {
        $role = $this->createRole(Role::WIZARD);

        $wand1 = new Wand();
        $wand1->setRole($role);
        $wand1->setName('Power Wand');
        $wand1->setPermissions(json_encode(['read', 'write']));
        $wand1->setCreatedAt(new \DateTimeImmutable());

        $wand2 = new Wand();
        $wand2->setRole($role);
        $wand2->setName('Simple Wand');
        $wand2->setPermissions(json_encode(['read']));
        $wand2->setCreatedAt(new \DateTimeImmutable());

        $wand3 = new Wand();
        $wand3->setRole($role);
        $wand3->setName('Write Wand');
        $wand3->setPermissions(json_encode(['read', 'write']));
        $wand3->setCreatedAt(new \DateTimeImmutable());

        $role->addWand($wand1);
        $role->addWand($wand2);
        $role->addWand($wand3);

        $partitioned = $role->getWands()->partition(
            fn($key, $wand) => in_array('write', json_decode($wand->getPermissions(), true))
        );

        $this->assertCount(2, $partitioned[0]);
        $this->assertCount(1, $partitioned[1]);
    }
}
