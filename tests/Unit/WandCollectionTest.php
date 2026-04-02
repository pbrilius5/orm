<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\Wand;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class WandCollectionTest extends TestCase
{
    private function createWand(string $name, array $permissions, ?\DateTimeImmutable $expiresAt = null): Wand
    {
        $wand = new Wand();
        $wand->setName($name);
        $wand->setPermissions(json_encode($permissions));
        $wand->setCreatedAt(new \DateTimeImmutable());
        if ($expiresAt !== null) {
            $wand->setExpiresAt($expiresAt);
        }
        return $wand;
    }

    public function testWandHasPermissionReturnsTrue(): void
    {
        $wand = $this->createWand('Power Wand', ['read', 'write']);

        $this->assertTrue($wand->hasPermission('read'));
        $this->assertTrue($wand->hasPermission('write'));
    }

    public function testWandHasPermissionReturnsFalse(): void
    {
        $wand = $this->createWand('Simple Wand', ['read']);

        $this->assertFalse($wand->hasPermission('write'));
    }

    public function testWandIsExpiredReturnsTrue(): void
    {
        $wand = $this->createWand('Expired Wand', ['read'], new \DateTimeImmutable('-1 day'));

        $this->assertTrue($wand->isExpired());
    }

    public function testWandIsExpiredReturnsFalse(): void
    {
        $wand = $this->createWand('Active Wand', ['read'], new \DateTimeImmutable('+1 day'));

        $this->assertFalse($wand->isExpired());
    }

    public function testWandIsExpiredReturnsFalseWhenNoExpiration(): void
    {
        $wand = $this->createWand('Permanent Wand', ['read']);

        $this->assertFalse($wand->isExpired());
    }

    public function testWandGetName(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);

        $this->assertSame('Test Wand', $wand->getName());
    }

    public function testWandGetPermissions(): void
    {
        $wand = $this->createWand('Test Wand', ['read', 'write', 'delete']);

        $this->assertSame(json_encode(['read', 'write', 'delete']), $wand->getPermissions());
    }

    public function testWandGetCreatedAt(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);

        $this->assertInstanceOf(\DateTimeInterface::class, $wand->getCreatedAt());
    }

    public function testWandGetExpiresAt(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 day');
        $wand = $this->createWand('Test Wand', ['read'], $expiresAt);

        $this->assertSame($expiresAt, $wand->getExpiresAt());
    }

    public function testWandGetExpiresAtReturnsNull(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);

        $this->assertNull($wand->getExpiresAt());
    }

    public function testWandSetUser(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('password123');

        $wand->setUser($user);

        $this->assertSame($user, $wand->getUser());
    }

    public function testWandSetRole(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);
        $role = new Role();
        $role->setName(Role::WIZARD);

        $wand->setRole($role);

        $this->assertSame($role, $wand->getRole());
    }

    public function testWandSetRoleToNull(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);
        $role = new Role();
        $role->setName(Role::WIZARD);

        $wand->setRole($role);
        $wand->setRole(null);

        $this->assertNull($wand->getRole());
    }

    public function testWandSetName(): void
    {
        $wand = $this->createWand('Old Name', ['read']);

        $wand->setName('New Name');

        $this->assertSame('New Name', $wand->getName());
    }

    public function testWandSetPermissions(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);

        $wand->setPermissions(json_encode(['read', 'write', 'delete']));

        $this->assertSame(json_encode(['read', 'write', 'delete']), $wand->getPermissions());
    }

    public function testWandSetCreatedAt(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);
        $newDate = new \DateTimeImmutable('2020-01-01');

        $wand->setCreatedAt($newDate);

        $this->assertSame($newDate, $wand->getCreatedAt());
    }

    public function testWandSetExpiresAt(): void
    {
        $wand = $this->createWand('Test Wand', ['read']);
        $newDate = new \DateTimeImmutable('+30 days');

        $wand->setExpiresAt($newDate);

        $this->assertSame($newDate, $wand->getExpiresAt());
    }

    public function testWandSetExpiresAtToNull(): void
    {
        $wand = $this->createWand('Test Wand', ['read'], new \DateTimeImmutable('+1 day'));

        $wand->setExpiresAt(null);

        $this->assertNull($wand->getExpiresAt());
    }

    public function testWandHasMultiplePermissions(): void
    {
        $wand = $this->createWand('Full Access Wand', ['read', 'write', 'delete', 'admin']);

        $this->assertTrue($wand->hasPermission('read'));
        $this->assertTrue($wand->hasPermission('write'));
        $this->assertTrue($wand->hasPermission('delete'));
        $this->assertTrue($wand->hasPermission('admin'));
        $this->assertFalse($wand->hasPermission('superadmin'));
    }

    public function testWandHasNoPermissions(): void
    {
        $wand = $this->createWand('Empty Wand', []);

        $this->assertFalse($wand->hasPermission('read'));
        $this->assertFalse($wand->hasPermission('write'));
    }
}
