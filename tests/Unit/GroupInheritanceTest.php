<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\DesignerGroup;
use App\Entity\DeveloperGroup;
use App\Entity\Group;
use App\Entity\TesterGroup;
use App\Entity\User;
use App\Entity\UserGroup;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class GroupInheritanceTest extends TestCase
{
    public function testDeveloperGroupExtendsGroup(): void
    {
        $group = new DeveloperGroup();
        $this->assertInstanceOf(Group::class, $group);
    }

    public function testDesignerGroupExtendsGroup(): void
    {
        $group = new DesignerGroup();
        $this->assertInstanceOf(Group::class, $group);
    }

    public function testTesterGroupExtendsGroup(): void
    {
        $group = new TesterGroup();
        $this->assertInstanceOf(Group::class, $group);
    }

    public function testDeveloperGroupInstanceOfCheck(): void
    {
        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->assertInstanceOf(DeveloperGroup::class, $group);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertNotInstanceOf(DesignerGroup::class, $group);
        $this->assertNotInstanceOf(TesterGroup::class, $group);
    }

    public function testDesignerGroupInstanceOfCheck(): void
    {
        $group = new DesignerGroup();
        $group->setName('Designers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->assertInstanceOf(DesignerGroup::class, $group);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertNotInstanceOf(DeveloperGroup::class, $group);
        $this->assertNotInstanceOf(TesterGroup::class, $group);
    }

    public function testTesterGroupInstanceOfCheck(): void
    {
        $group = new TesterGroup();
        $group->setName('Testers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->assertInstanceOf(TesterGroup::class, $group);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertNotInstanceOf(DeveloperGroup::class, $group);
        $this->assertNotInstanceOf(DesignerGroup::class, $group);
    }

    public function testBaseGroupInstanceOfCheck(): void
    {
        $group = new Group();
        $group->setName('Users');
        $group->setCreatedAt(new \DateTimeImmutable());

        $this->assertInstanceOf(Group::class, $group);
        $this->assertNotInstanceOf(DeveloperGroup::class, $group);
        $this->assertNotInstanceOf(DesignerGroup::class, $group);
        $this->assertNotInstanceOf(TesterGroup::class, $group);
    }

    public function testStiDiscriminatorViaMatchExpression(): void
    {
        $entities = [
            new DeveloperGroup(),
            new DesignerGroup(),
            new TesterGroup(),
            new Group(),
        ];

        foreach ($entities as $entity) {
            $entity->setName('Test');
            $entity->setCreatedAt(new \DateTimeImmutable());

            $result = match (true) {
                $entity instanceof DeveloperGroup => 'developer',
                $entity instanceof DesignerGroup => 'designer',
                $entity instanceof TesterGroup => 'tester',
                $entity instanceof Group => 'group',
                default => 'unknown',
            };

            $expected = match (true) {
                $entity instanceof DeveloperGroup => 'developer',
                $entity instanceof DesignerGroup => 'designer',
                $entity instanceof TesterGroup => 'tester',
                $entity instanceof Group => 'group',
                default => 'unknown',
            };

            $this->assertSame($expected, $result);
        }
    }

    public function testUserGroupCollection(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group1 = new DeveloperGroup();
        $group1->setName('Developers');
        $group1->setCreatedAt(new \DateTimeImmutable());

        $group2 = new DesignerGroup();
        $group2->setName('Designers');
        $group2->setCreatedAt(new \DateTimeImmutable());

        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->assertCount(2, $user->getUserGroups());
        $this->assertTrue($user->hasGroup('Developers'));
        $this->assertTrue($user->hasGroup('Designers'));
        $this->assertFalse($user->hasGroup('Testers'));
    }

    public function testUserGroupRemove(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $user->addGroup($group);
        $this->assertCount(1, $user->getUserGroups());

        $user->removeGroup($group);
        $this->assertCount(0, $user->getUserGroups());
    }

    public function testUserAddDuplicateGroupDoesNotDuplicate(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $user->addGroup($group);
        $user->addGroup($group);

        $this->assertCount(1, $user->getUserGroups());
    }

    public function testUserGroupIsActive(): void
    {
        $userGroup = new UserGroup();
        $this->assertTrue($userGroup->isActive());
    }

    public function testUserGroupIsExpired(): void
    {
        $userGroup = new UserGroup();
        $userGroup->setExpiresAt(new \DateTimeImmutable('-1 day'));
        $this->assertTrue($userGroup->isExpired());
        $this->assertFalse($userGroup->isActive());
    }

    public function testUserGroupNotExpired(): void
    {
        $userGroup = new UserGroup();
        $userGroup->setExpiresAt(new \DateTimeImmutable('+1 day'));
        $this->assertFalse($userGroup->isExpired());
        $this->assertTrue($userGroup->isActive());
    }

    public function testUserGetGroupsReturnsNames(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $user->addGroup($group);

        $groups = $user->getGroups();
        $this->assertSame(['Developers'], $groups);
    }

    public function testUserGetAllGroupsReturnsEntities(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $user->addGroup($group);

        $groups = $user->getAllGroups();
        $this->assertCount(1, $groups);
        $this->assertInstanceOf(DeveloperGroup::class, $groups[0]);
    }

    public function testGroupUsersConstant(): void
    {
        $this->assertSame('Users', Group::USERS);
    }

    public function testUserGroupBidirectionalRelationship(): void
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);

        $user->getUserGroups()->add($userGroup);
        $group->addUserGroup($userGroup);

        $this->assertSame($user, $userGroup->getUser());
        $this->assertSame($group, $userGroup->getGroup());
        $this->assertCount(1, $user->getUserGroups());
        $this->assertCount(1, $group->getUserGroups());
    }

    public function testUserGroupSettersAndGetters(): void
    {
        $userGroup = new UserGroup();
        $id = Uuid::uuid4();
        $userGroup->setId($id);

        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password');

        $group = new DeveloperGroup();
        $group->setName('Developers');
        $group->setCreatedAt(new \DateTimeImmutable());

        $userGroup->setUser($user);
        $userGroup->setGroup($group);

        $grantedAt = new \DateTimeImmutable();
        $userGroup->setGrantedAt($grantedAt);

        $expiresAt = new \DateTimeImmutable('+1 day');
        $userGroup->setExpiresAt($expiresAt);

        $this->assertSame($id, $userGroup->getId());
        $this->assertSame($user, $userGroup->getUser());
        $this->assertSame($group, $userGroup->getGroup());
        $this->assertSame($grantedAt, $userGroup->getGrantedAt());
        $this->assertSame($expiresAt, $userGroup->getExpiresAt());
    }
}
