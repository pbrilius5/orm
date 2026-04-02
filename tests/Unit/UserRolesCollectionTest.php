<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\Wand;
use App\Entity\Patronus;
use App\Entity\InvisibilityCloak;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\TestCase;

class UserRolesCollectionTest extends TestCase
{
    public function testGetAllRolesReturnsOnlyActive(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');

        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $architectRole = new Role();
        $architectRole->setName(Role::ARCHITECT);

        $expiredUserRole = new UserRole();
        $expiredUserRole->setRole($wizardRole);
        $expiredUserRole->setTeam($team);
        $expiredUserRole->setGrantedAt(new \DateTimeImmutable('-2 days'));
        $expiredUserRole->setExpiresAt(new \DateTimeImmutable('-1 day'));

        $activeUserRole = new UserRole();
        $activeUserRole->setRole($architectRole);
        $activeUserRole->setTeam($team);
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

        $team = new Team();
        $team->setName('Character Art');
        $team->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $gameMasterRole = new Role();
        $gameMasterRole->setName(Role::GAME_MASTER);

        $wizardUserRole = new UserRole();
        $wizardUserRole->setRole($wizardRole);
        $wizardUserRole->setTeam($team);
        $wizardUserRole->setGrantedAt(new \DateTimeImmutable());

        $gmUserRole = new UserRole();
        $gmUserRole->setRole($gameMasterRole);
        $gmUserRole->setTeam($team);
        $gmUserRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($wizardUserRole);
        $user->getUserRoles()->add($gmUserRole);

        $roleNames = $user->getRoleNames();

        $this->assertCount(2, $roleNames);
        $this->assertContains(Role::WIZARD, $roleNames);
        $this->assertContains(Role::GAME_MASTER, $roleNames);
    }

    public function testGetRolesForTeamReturnsOnlyMatchingRoles(): void
    {
        $user = new User();
        $user->setEmail('cross@example.com');
        $user->setPassword('password123');

        $teamA = new Team();
        $teamA->setName('Level Design');
        $teamA->setCreatedAt(new \DateTimeImmutable());

        $teamB = new Team();
        $teamB->setName('Audio Engineering');
        $teamB->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $architectRole = new Role();
        $architectRole->setName(Role::ARCHITECT);

        $userRoleA = new UserRole();
        $userRoleA->setRole($wizardRole);
        $userRoleA->setTeam($teamA);
        $userRoleA->setGrantedAt(new \DateTimeImmutable());

        $userRoleB = new UserRole();
        $userRoleB->setRole($architectRole);
        $userRoleB->setTeam($teamB);
        $userRoleB->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRoleA);
        $user->getUserRoles()->add($userRoleB);

        $teamARoles = $user->getRolesForTeam($teamA);

        $this->assertCount(1, $teamARoles);
        $this->assertSame(Role::WIZARD, $teamARoles[0]->getName());
    }

    public function testCollectionCountReturnsCorrectNumber(): void
    {
        $user = new User();
        $user->setEmail('count@example.com');
        $user->setPassword('password123');

        $this->assertInstanceOf(Collection::class, $user->getUserRoles());
        $this->assertCount(0, $user->getUserRoles());

        $team = new Team();
        $team->setName('Test Team');
        $team->setCreatedAt(new \DateTimeImmutable());

        $role = new Role();
        $role->setName(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setTeam($team);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRole);

        $this->assertCount(1, $user->getUserRoles());
    }

    public function testHasRoleUsesCollectionFilter(): void
    {
        $user = new User();
        $user->setEmail('filter@example.com');
        $user->setPassword('password123');

        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($wizardRole);
        $userRole->setTeam($team);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRole);

        $this->assertTrue($user->hasRole(Role::WIZARD, $team));
        $this->assertFalse($user->hasRole(Role::ARCHITECT, $team));
    }

    public function testGetExpiredWandsReturnsOnlyExpired(): void
    {
        $user = new User();
        $user->setEmail('wand@example.com');
        $user->setPassword('password123');

        $role = new Role();
        $role->setName(Role::WIZARD);

        $expiredWand = new Wand();
        $expiredWand->setUser($user);
        $expiredWand->setRole($role);
        $expiredWand->setName('Expired Wand');
        $expiredWand->setPermissions(json_encode(['read']));
        $expiredWand->setCreatedAt(new \DateTimeImmutable('-2 days'));
        $expiredWand->setExpiresAt(new \DateTimeImmutable('-1 day'));

        $activeWand = new Wand();
        $activeWand->setUser($user);
        $activeWand->setRole($role);
        $activeWand->setName('Active Wand');
        $activeWand->setPermissions(json_encode(['read', 'write']));
        $activeWand->setCreatedAt(new \DateTimeImmutable());

        $user->getWands()->add($expiredWand);
        $user->getWands()->add($activeWand);

        $expiredWands = $user->getExpiredWands();
        $activeWands = $user->getActiveWands();

        $this->assertCount(1, $expiredWands);
        $this->assertCount(1, $activeWands);
        $this->assertSame('Expired Wand', $expiredWands[0]->getName());
        $this->assertSame('Active Wand', $activeWands[0]->getName());
    }

    public function testGetValidPatronusesReturnsOnlyValid(): void
    {
        $user = new User();
        $user->setEmail('patronus@example.com');
        $user->setPassword('password123');

        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $role = new Role();
        $role->setName(Role::WIZARD);

        $validPatronus = new Patronus();
        $validPatronus->setUser($user);
        $validPatronus->setRole($role);
        $validPatronus->setTeam($team);
        $validPatronus->setIssuedAt(new \DateTimeImmutable());
        $validPatronus->setExpiresAt(new \DateTimeImmutable('+24 hours'));

        $user->getPatronuses()->add($validPatronus);

        $validPatronuses = $user->getValidPatronuses();

        $this->assertCount(1, $validPatronuses);
        $this->assertTrue($validPatronuses[0]->isValid());
    }

    public function testGetActiveInvisibilityCloaksReturnsOnlyActive(): void
    {
        $user = new User();
        $user->setEmail('cloak@example.com');
        $user->setPassword('password123');

        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $activeCloak = new InvisibilityCloak();
        $activeCloak->setUser($user);
        $activeCloak->setTeam($team);
        $activeCloak->setGrantedAt(new \DateTimeImmutable());
        $activeCloak->activate();

        $inactiveCloak = new InvisibilityCloak();
        $inactiveCloak->setUser($user);
        $inactiveCloak->setTeam($team);
        $inactiveCloak->setGrantedAt(new \DateTimeImmutable('-1 day'));
        $inactiveCloak->deactivate();

        $user->getInvisibilityCloaks()->add($activeCloak);
        $user->getInvisibilityCloaks()->add($inactiveCloak);

        $activeCloaks = $user->getActiveInvisibilityCloaks();

        $this->assertCount(1, $activeCloaks);
        $this->assertTrue($activeCloaks[0]->isActive());
    }

    public function testCollectionFirstReturnsFirstMatchingElement(): void
    {
        $user = new User();
        $user->setEmail('first@example.com');
        $user->setPassword('password123');

        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $architectRole = new Role();
        $architectRole->setName(Role::ARCHITECT);

        $wizardUserRole = new UserRole();
        $wizardUserRole->setRole($wizardRole);
        $wizardUserRole->setTeam($team);
        $wizardUserRole->setGrantedAt(new \DateTimeImmutable());

        $architectUserRole = new UserRole();
        $architectUserRole->setRole($architectRole);
        $architectUserRole->setTeam($team);
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

        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $userRole = new UserRole();
        $userRole->setRole($wizardRole);
        $userRole->setTeam($team);
        $userRole->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRole);

        $hasWizard = $user->getUserRoles()
            ->exists(fn($key, $ur) => $ur->getRole()->getName() === Role::WIZARD);

        $this->assertTrue($hasWizard);
    }

    public function testCollectionMatchingWithCriteria(): void
    {
        $user = new User();
        $user->setEmail('criteria@example.com');
        $user->setPassword('password123');

        $teamA = new Team();
        $teamA->setName('Level Design');
        $teamA->setCreatedAt(new \DateTimeImmutable());

        $teamB = new Team();
        $teamB->setName('Audio Engineering');
        $teamB->setCreatedAt(new \DateTimeImmutable());

        $wizardRole = new Role();
        $wizardRole->setName(Role::WIZARD);

        $userRoleA1 = new UserRole();
        $userRoleA1->setRole($wizardRole);
        $userRoleA1->setTeam($teamA);
        $userRoleA1->setGrantedAt(new \DateTimeImmutable());

        $userRoleA2 = new UserRole();
        $userRoleA2->setRole($wizardRole);
        $userRoleA2->setTeam($teamA);
        $userRoleA2->setGrantedAt(new \DateTimeImmutable('+1 day'));
        $userRoleA2->setExpiresAt(new \DateTimeImmutable('+2 days'));

        $userRoleB = new UserRole();
        $userRoleB->setRole($wizardRole);
        $userRoleB->setTeam($teamB);
        $userRoleB->setGrantedAt(new \DateTimeImmutable());

        $user->getUserRoles()->add($userRoleA1);
        $user->getUserRoles()->add($userRoleA2);
        $user->getUserRoles()->add($userRoleB);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('team', $teamA));

        $teamARoles = $user->getUserRoles()->matching($criteria);

        $this->assertCount(2, $teamARoles);
    }

    public function testTeamGetActiveUsersCount(): void
    {
        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setPassword('password123');

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setPassword('password123');

        $team->addUser($user1);
        $team->addUser($user2);

        $this->assertCount(2, $team->getUsers());
        $this->assertSame(2, $team->countUsers());
    }
}
