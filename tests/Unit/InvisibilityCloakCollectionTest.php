<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\InvisibilityCloak;
use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class InvisibilityCloakCollectionTest extends TestCase
{
    private function createCloak(?\DateTimeImmutable $expiresAt = null, bool $isActive = true): InvisibilityCloak
    {
        $cloak = new InvisibilityCloak();
        $cloak->setGrantedAt(new \DateTimeImmutable());
        if ($expiresAt !== null) {
            $cloak->setExpiresAt($expiresAt);
        }
        if (!$isActive) {
            $cloak->deactivate();
        }
        return $cloak;
    }

    public function testCloakIsActiveReturnsTrueWhenActive(): void
    {
        $cloak = $this->createCloak(null, true);

        $this->assertTrue($cloak->isActive());
    }

    public function testCloakIsActiveReturnsFalseWhenDeactivated(): void
    {
        $cloak = $this->createCloak(null, false);

        $this->assertFalse($cloak->isActive());
    }

    public function testCloakIsActiveReturnsFalseWhenExpired(): void
    {
        $cloak = $this->createCloak(new \DateTimeImmutable('-1 day'), true);

        $this->assertFalse($cloak->isActive());
    }

    public function testCloakIsActiveReturnsTrueWhenNotExpired(): void
    {
        $cloak = $this->createCloak(new \DateTimeImmutable('+1 day'), true);

        $this->assertTrue($cloak->isActive());
    }

    public function testCloakIsActiveReturnsTrueWhenNoExpiration(): void
    {
        $cloak = $this->createCloak(null, true);

        $this->assertTrue($cloak->isActive());
    }

    public function testCloakDeactivate(): void
    {
        $cloak = $this->createCloak(null, true);

        $cloak->deactivate();

        $this->assertFalse($cloak->isActive());
    }

    public function testCloakActivate(): void
    {
        $cloak = $this->createCloak(null, false);

        $cloak->activate();

        $this->assertTrue($cloak->isActive());
    }

    public function testCloakActivateThenDeactivate(): void
    {
        $cloak = $this->createCloak(null, true);

        $cloak->deactivate();
        $this->assertFalse($cloak->isActive());

        $cloak->activate();
        $this->assertTrue($cloak->isActive());
    }

    public function testCloakGetGrantedAt(): void
    {
        $cloak = $this->createCloak();

        $this->assertInstanceOf(\DateTimeInterface::class, $cloak->getGrantedAt());
    }

    public function testCloakGetExpiresAt(): void
    {
        $expiresAt = new \DateTimeImmutable('+30 days');
        $cloak = $this->createCloak($expiresAt);

        $this->assertSame($expiresAt, $cloak->getExpiresAt());
    }

    public function testCloakGetExpiresAtReturnsNull(): void
    {
        $cloak = $this->createCloak();

        $this->assertNull($cloak->getExpiresAt());
    }

    public function testCloakSetUser(): void
    {
        $cloak = $this->createCloak();
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('password123');

        $cloak->setUser($user);

        $this->assertSame($user, $cloak->getUser());
    }

    public function testCloakSetTeam(): void
    {
        $cloak = $this->createCloak();
        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $cloak->setTeam($team);

        $this->assertSame($team, $cloak->getTeam());
    }

    public function testCloakSetGrantedAt(): void
    {
        $cloak = $this->createCloak();
        $newDate = new \DateTimeImmutable('2020-01-01');

        $cloak->setGrantedAt($newDate);

        $this->assertSame($newDate, $cloak->getGrantedAt());
    }

    public function testCloakSetExpiresAt(): void
    {
        $cloak = $this->createCloak();
        $newDate = new \DateTimeImmutable('+60 days');

        $cloak->setExpiresAt($newDate);

        $this->assertSame($newDate, $cloak->getExpiresAt());
    }

    public function testCloakSetExpiresAtToNull(): void
    {
        $cloak = $this->createCloak(new \DateTimeImmutable('+1 day'));

        $cloak->setExpiresAt(null);

        $this->assertNull($cloak->getExpiresAt());
    }

    public function testCloakIdIsNullInitially(): void
    {
        $cloak = $this->createCloak();

        $this->assertNull($cloak->getId());
    }

    public function testCloakIsActiveByDefault(): void
    {
        $cloak = $this->createCloak();

        $this->assertTrue($cloak->isActive());
    }

    public function testCloakGrantedAtIsSetInConstructor(): void
    {
        $cloak = new InvisibilityCloak();

        $this->assertInstanceOf(\DateTimeInterface::class, $cloak->getGrantedAt());
    }

    public function testCloakExpiredBeforeGranted(): void
    {
        $cloak = new InvisibilityCloak();
        $cloak->setGrantedAt(new \DateTimeImmutable('+1 day'));
        $cloak->setExpiresAt(new \DateTimeImmutable('-1 day'));

        $this->assertFalse($cloak->isActive());
    }
}
