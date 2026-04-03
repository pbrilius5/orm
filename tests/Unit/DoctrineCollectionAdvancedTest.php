<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\TestCase;

class DoctrineCollectionAdvancedTest extends TestCase
{
    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->setName($name);
        return $role;
    }

    private function createUserRole(Role $role, ?\DateTimeImmutable $expiresAt = null): UserRole
    {
        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setGrantedAt(new \DateTimeImmutable());
        if ($expiresAt !== null) {
            $userRole->setExpiresAt($expiresAt);
        }
        return $userRole;
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password123');
        return $user;
    }

    public function testCollectionSliceReturnsSubset(): void
    {
        $user = $this->createUser('slice@example.com');
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 5; $i++) {
            $userRole = $this->createUserRole($role);
            $user->getUserRoles()->add($userRole);
        }

        $sliced = $user->getUserRoles()->slice(1, 3);

        $this->assertCount(3, $sliced);
    }

    public function testCollectionSliceWithOffsetAndLimit(): void
    {
        $user = $this->createUser('slice-offset@example.com');
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 10; $i++) {
            $userRole = $this->createUserRole($role);
            $user->getUserRoles()->add($userRole);
        }

        $sliced = $user->getUserRoles()->slice(2, 4);

        $this->assertCount(4, $sliced);
    }

    public function testPartitionSplitsIntoTwoCollections(): void
    {
        $user = $this->createUser('partition@example.com');
        $wizardRole = $this->createRole(Role::WIZARD);
        $architectRole = $this->createRole(Role::ARCHITECT);

        $user->getUserRoles()->add($this->createUserRole($wizardRole));
        $user->getUserRoles()->add($this->createUserRole($architectRole));
        $user->getUserRoles()->add($this->createUserRole($wizardRole));

        $partitioned = $user->getUserRoles()->partition(
            fn($key, $ur) => $ur->getRole()->getName() === Role::WIZARD
        );

        $this->assertCount(2, $partitioned[0]);
        $this->assertCount(1, $partitioned[1]);
    }

    public function testCollectionAddDoesNotDuplicate(): void
    {
        $user = $this->createUser('add@example.com');
        $role = $this->createRole(Role::WIZARD);
        $userRole1 = $this->createUserRole($role);
        $userRole2 = $this->createUserRole($role);

        $user->getUserRoles()->add($userRole1);
        $user->getUserRoles()->add($userRole2);

        $this->assertCount(2, $user->getUserRoles());

        $this->assertTrue($user->getUserRoles()->contains($userRole1));
        $this->assertTrue($user->getUserRoles()->contains($userRole2));
    }

    public function testCollectionRemoveElement(): void
    {
        $user = $this->createUser('remove@example.com');
        $role = $this->createRole(Role::WIZARD);
        $userRole = $this->createUserRole($role);

        $user->getUserRoles()->add($userRole);
        $this->assertCount(1, $user->getUserRoles());

        $user->getUserRoles()->removeElement($userRole);
        $this->assertCount(0, $user->getUserRoles());
    }

    public function testCollectionClearRemovesAll(): void
    {
        $user = $this->createUser('clear@example.com');
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 5; $i++) {
            $user->getUserRoles()->add($this->createUserRole($role));
        }

        $this->assertCount(5, $user->getUserRoles());

        $user->getUserRoles()->clear();

        $this->assertCount(0, $user->getUserRoles());
    }

    public function testCollectionContainsReturnsCorrectBoolean(): void
    {
        $user = $this->createUser('contains@example.com');
        $role = $this->createRole(Role::WIZARD);
        $userRole = $this->createUserRole($role);

        $this->assertFalse($user->getUserRoles()->contains($userRole));

        $user->getUserRoles()->add($userRole);

        $this->assertTrue($user->getUserRoles()->contains($userRole));
    }

    public function testCollectionGetByNumericKey(): void
    {
        $user = $this->createUser('get@example.com');
        $role = $this->createRole(Role::WIZARD);

        $userRole1 = $this->createUserRole($role);
        $userRole2 = $this->createUserRole($role);

        $user->getUserRoles()->add($userRole1);
        $user->getUserRoles()->add($userRole2);

        $this->assertSame($userRole1, $user->getUserRoles()->get(0));
        $this->assertSame($userRole2, $user->getUserRoles()->get(1));
        $this->assertNull($user->getUserRoles()->get(99));
    }

    public function testCollectionSetAddsNewElement(): void
    {
        $user = $this->createUser('set@example.com');
        $role = $this->createRole(Role::WIZARD);
        $userRole = $this->createUserRole($role);

        $user->getUserRoles()->set(0, $userRole);

        $this->assertCount(1, $user->getUserRoles());
        $this->assertSame($userRole, $user->getUserRoles()->get(0));
    }

    public function testCollectionRemoveByKey(): void
    {
        $user = $this->createUser('remove-key@example.com');
        $role = $this->createRole(Role::WIZARD);
        $userRole = $this->createUserRole($role);

        $user->getUserRoles()->set(0, $userRole);
        $this->assertCount(1, $user->getUserRoles());

        $user->getUserRoles()->remove(0);

        $this->assertCount(0, $user->getUserRoles());
    }

    public function testCollectionCanBeIteratedWithForeach(): void
    {
        $user = $this->createUser('iterate@example.com');
        $role = $this->createRole(Role::WIZARD);

        for ($i = 0; $i < 3; $i++) {
            $user->getUserRoles()->add($this->createUserRole($role));
        }

        $count = 0;
        foreach ($user->getUserRoles() as $userRole) {
            $this->assertInstanceOf(UserRole::class, $userRole);
            $count++;
        }

        $this->assertSame(3, $count);
    }

    public function testCollectionIsEmptyReturnsTrueForEmpty(): void
    {
        $user = $this->createUser('empty@example.com');

        $this->assertTrue($user->getUserRoles()->isEmpty());
    }

    public function testCollectionIsEmptyReturnsFalseForNonEmpty(): void
    {
        $user = $this->createUser('not-empty@example.com');
        $role = $this->createRole(Role::WIZARD);

        $user->getUserRoles()->add($this->createUserRole($role));

        $this->assertFalse($user->getUserRoles()->isEmpty());
    }

    public function testCollectionMatchingWithOrderBy(): void
    {
        $user = $this->createUser('orderby@example.com');
        $role = $this->createRole(Role::WIZARD);

        $ur1 = $this->createUserRole($role);
        $ur1->setGrantedAt(new \DateTimeImmutable('-3 days'));

        $ur2 = $this->createUserRole($role);
        $ur2->setGrantedAt(new \DateTimeImmutable('-1 day'));

        $ur3 = $this->createUserRole($role);
        $ur3->setGrantedAt(new \DateTimeImmutable('-2 days'));

        $user->getUserRoles()->add($ur1);
        $user->getUserRoles()->add($ur2);
        $user->getUserRoles()->add($ur3);

        $criteria = Criteria::create()
            ->orderBy(['grantedAt' => Criteria::ASC]);

        $sorted = $user->getUserRoles()->matching($criteria);

        $this->assertCount(3, $sorted);
        $this->assertSame($ur1, $sorted->first());
    }

    public function testCollectionMatchingWithMultipleWhere(): void
    {
        $user = $this->createUser('multiwhere@example.com');
        $wizardRole = $this->createRole(Role::WIZARD);
        $architectRole = $this->createRole(Role::ARCHITECT);

        $ur1 = $this->createUserRole($wizardRole);
        $ur2 = $this->createUserRole($architectRole);
        $ur3 = $this->createUserRole($wizardRole);

        $ur2->setExpiresAt(new \DateTimeImmutable('-1 day'));

        $user->getUserRoles()->add($ur1);
        $user->getUserRoles()->add($ur2);
        $user->getUserRoles()->add($ur3);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('expiresAt', null));

        $result = $user->getUserRoles()->matching($criteria);

        $this->assertCount(2, $result);
    }

    public function testCollectionMapToDifferentType(): void
    {
        $user = $this->createUser('map-type@example.com');
        $role = $this->createRole(Role::WIZARD);

        $user->getUserRoles()->add($this->createUserRole($role));
        $user->getUserRoles()->add($this->createUserRole($role));

        $grantedAts = $user->getUserRoles()
            ->map(fn($ur) => $ur->getGrantedAt())
            ->toArray();

        $this->assertCount(2, $grantedAts);
        foreach ($grantedAts as $grantedAt) {
            $this->assertInstanceOf(\DateTimeInterface::class, $grantedAt);
        }
    }

    public function testCollectionFilterWithComplexCondition(): void
    {
        $user = $this->createUser('complex-filter@example.com');
        $wizardRole = $this->createRole(Role::WIZARD);
        $architectRole = $this->createRole(Role::ARCHITECT);

        $ur1 = $this->createUserRole($wizardRole);
        $ur2 = $this->createUserRole($architectRole);
        $ur2->setExpiresAt(new \DateTimeImmutable('-1 day'));
        $ur3 = $this->createUserRole($wizardRole);

        $user->getUserRoles()->add($ur1);
        $user->getUserRoles()->add($ur2);
        $user->getUserRoles()->add($ur3);

        $filtered = $user->getUserRoles()
            ->filter(fn($ur) => $ur->getRole()->getName() === Role::WIZARD && $ur->isActive())
            ->toArray();

        $this->assertCount(2, $filtered);
    }

    public function testCollectionGetKeysReturnsNumericKeys(): void
    {
        $user = $this->createUser('keys@example.com');
        $role = $this->createRole(Role::WIZARD);

        $user->getUserRoles()->add($this->createUserRole($role));
        $user->getUserRoles()->add($this->createUserRole($role));

        $keys = $user->getUserRoles()->getKeys();

        $this->assertCount(2, $keys);
        $this->assertSame([0, 1], $keys);
    }

    public function testCollectionGetValuesReturnsElements(): void
    {
        $user = $this->createUser('values@example.com');
        $role = $this->createRole(Role::WIZARD);

        $ur1 = $this->createUserRole($role);
        $ur2 = $this->createUserRole($role);

        $user->getUserRoles()->add($ur1);
        $user->getUserRoles()->add($ur2);

        $values = $user->getUserRoles()->getValues();

        $this->assertCount(2, $values);
        $this->assertContains($ur1, $values);
        $this->assertContains($ur2, $values);
    }

    public function testCollectionForAllReturnsTrueWhenAllMatch(): void
    {
        $user = $this->createUser('forall-true@example.com');
        $role = $this->createRole(Role::WIZARD);

        $user->getUserRoles()->add($this->createUserRole($role));
        $user->getUserRoles()->add($this->createUserRole($role));
        $user->getUserRoles()->add($this->createUserRole($role));

        $allWizard = $user->getUserRoles()
            ->forAll(fn($key, $ur) => $ur->getRole()->getName() === Role::WIZARD);

        $this->assertTrue($allWizard);
    }

    public function testCollectionForAllReturnsFalseWhenSomeDontMatch(): void
    {
        $user = $this->createUser('forall-false@example.com');
        $wizardRole = $this->createRole(Role::WIZARD);
        $architectRole = $this->createRole(Role::ARCHITECT);

        $user->getUserRoles()->add($this->createUserRole($wizardRole));
        $user->getUserRoles()->add($this->createUserRole($architectRole));

        $allWizard = $user->getUserRoles()
            ->forAll(fn($key, $ur) => $ur->getRole()->getName() === Role::WIZARD);

        $this->assertFalse($allWizard);
    }

    public function testCollectionFirstReturnsFalseWhenEmpty(): void
    {
        $user = $this->createUser('first-empty@example.com');

        $this->assertFalse($user->getUserRoles()->first());
    }

    public function testCollectionFilterOnEmptyCollection(): void
    {
        $user = $this->createUser('filter-empty@example.com');

        $filtered = $user->getUserRoles()
            ->filter(fn($ur) => $ur->isActive())
            ->toArray();

        $this->assertCount(0, $filtered);
    }

    public function testCollectionMapOnEmptyCollection(): void
    {
        $user = $this->createUser('map-empty@example.com');

        $mapped = $user->getUserRoles()
            ->map(fn($ur) => $ur->getRole()->getName())
            ->toArray();

        $this->assertCount(0, $mapped);
    }
}
