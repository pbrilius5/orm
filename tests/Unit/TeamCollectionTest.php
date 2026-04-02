<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\InvisibilityCloak;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\TestCase;

class TeamCollectionTest extends TestCase
{
    private function createTeam(string $name): Team
    {
        $team = new Team();
        $team->setName($name);
        $team->setCreatedAt(new \DateTimeImmutable());
        return $team;
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password123');
        return $user;
    }

    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->setName($name);
        return $role;
    }

    public function testTeamUsersCollectionIsEmptyInitially(): void
    {
        $team = $this->createTeam('Empty Team');

        $this->assertTrue($team->getUsers()->isEmpty());
        $this->assertCount(0, $team->getUsers());
    }

    public function testTeamAddUserIncreasesCount(): void
    {
        $team = $this->createTeam('Level Design');
        $user = $this->createUser('user1@example.com');

        $team->addUser($user);

        $this->assertCount(1, $team->getUsers());
        $this->assertTrue($team->getUsers()->contains($user));
    }

    public function testTeamAddUserDoesNotDuplicate(): void
    {
        $team = $this->createTeam('Level Design');
        $user = $this->createUser('user1@example.com');

        $team->addUser($user);
        $team->addUser($user);

        $this->assertCount(1, $team->getUsers());
    }

    public function testTeamRemoveUserDecreasesCount(): void
    {
        $team = $this->createTeam('Level Design');
        $user = $this->createUser('user1@example.com');

        $team->addUser($user);
        $this->assertCount(1, $team->getUsers());

        $team->removeUser($user);

        $this->assertCount(0, $team->getUsers());
    }

    public function testTeamClearUsers(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 5; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $this->assertCount(5, $team->getUsers());

        $team->getUsers()->clear();

        $this->assertCount(0, $team->getUsers());
    }

    public function testTeamGetUsersReturnsCollection(): void
    {
        $team = $this->createTeam('Level Design');

        $this->assertInstanceOf(Collection::class, $team->getUsers());
    }

    public function testTeamCountUsers(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 3; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $this->assertSame(3, $team->countUsers());
    }

    public function testTeamUsersCanBeIterated(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 3; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $count = 0;
        foreach ($team->getUsers() as $user) {
            $this->assertInstanceOf(User::class, $user);
            $count++;
        }

        $this->assertSame(3, $count);
    }

    public function testTeamRolesCollectionIsEmptyInitially(): void
    {
        $team = $this->createTeam('Empty Team');

        $this->assertTrue($team->getRoles()->isEmpty());
    }

    public function testTeamAddRoleIncreasesCount(): void
    {
        $team = $this->createTeam('Level Design');
        $role = $this->createRole(Role::WIZARD);

        $team->addRole($role);

        $this->assertCount(1, $team->getRoles());
        $this->assertTrue($team->getRoles()->contains($role));
    }

    public function testTeamAddRoleSetsTeamOnRole(): void
    {
        $team = $this->createTeam('Level Design');
        $role = $this->createRole(Role::WIZARD);

        $team->addRole($role);

        $this->assertSame($team, $role->getTeam());
    }

    public function testTeamRemoveRoleDecreasesCount(): void
    {
        $team = $this->createTeam('Level Design');
        $role = $this->createRole(Role::WIZARD);

        $team->addRole($role);
        $this->assertCount(1, $team->getRoles());

        $team->removeRole($role);

        $this->assertCount(0, $team->getRoles());
    }

    public function testTeamRemoveRoleSetsTeamToNull(): void
    {
        $team = $this->createTeam('Level Design');
        $role = $this->createRole(Role::WIZARD);

        $team->addRole($role);
        $team->removeRole($role);

        $this->assertNull($role->getTeam());
    }

    public function testTeamInvisibilityCloaksCollectionIsEmptyInitially(): void
    {
        $team = $this->createTeam('Empty Team');

        $this->assertTrue($team->getInvisibilityCloaks()->isEmpty());
    }

    public function testTeamAddInvisibilityCloakIncreasesCount(): void
    {
        $team = $this->createTeam('Level Design');
        $user = $this->createUser('cloak@example.com');

        $cloak = new InvisibilityCloak();
        $cloak->setUser($user);
        $cloak->setTeam($team);
        $cloak->setGrantedAt(new \DateTimeImmutable());
        $cloak->activate();

        $team->addInvisibilityCloak($cloak);

        $this->assertCount(1, $team->getInvisibilityCloaks());
    }

    public function testTeamFilterUsersByCondition(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 5; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $filtered = $team->getUsers()
            ->filter(fn($user) => str_contains($user->getEmail(), 'user0'))
            ->toArray();

        $this->assertCount(1, $filtered);
    }

    public function testTeamSliceUsers(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 10; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $sliced = $team->getUsers()->slice(2, 3);

        $this->assertCount(3, $sliced);
    }

    public function testTeamFirstUser(): void
    {
        $team = $this->createTeam('Level Design');
        $user = $this->createUser('first@example.com');

        $team->addUser($user);

        $first = $team->getUsers()->first();

        $this->assertNotFalse($first);
        $this->assertSame($user, $first);
    }

    public function testTeamFirstReturnsFalseWhenEmpty(): void
    {
        $team = $this->createTeam('Empty Team');

        $this->assertFalse($team->getUsers()->first());
    }

    public function testTeamPartitionUsers(): void
    {
        $team = $this->createTeam('Level Design');

        $team->addUser($this->createUser('admin@example.com'));
        $team->addUser($this->createUser('user@example.com'));
        $team->addUser($this->createUser('admin2@example.com'));

        $partitioned = $team->getUsers()->partition(
            fn($key, $user) => str_contains($user->getEmail(), 'admin')
        );

        $this->assertCount(2, $partitioned[0]);
        $this->assertCount(1, $partitioned[1]);
    }

    public function testTeamForAllUsers(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 3; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $allHaveEmail = $team->getUsers()
            ->forAll(fn($key, $user) => !empty($user->getEmail()));

        $this->assertTrue($allHaveEmail);
    }

    public function testTeamGetUserKeys(): void
    {
        $team = $this->createTeam('Level Design');

        for ($i = 0; $i < 3; $i++) {
            $team->addUser($this->createUser("user{$i}@example.com"));
        }

        $keys = $team->getUsers()->getKeys();

        $this->assertSame([0, 1, 2], $keys);
    }

    public function testTeamGetUserValues(): void
    {
        $team = $this->createTeam('Level Design');
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');

        $team->addUser($user1);
        $team->addUser($user2);

        $values = $team->getUsers()->getValues();

        $this->assertCount(2, $values);
        $this->assertContains($user1, $values);
        $this->assertContains($user2, $values);
    }
}
