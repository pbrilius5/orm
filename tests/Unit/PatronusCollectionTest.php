<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Patronus;
use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PatronusCollectionTest extends TestCase
{
    private function createPatronus(?\DateTimeImmutable $expiresAt = null): Patronus
    {
        $patronus = new Patronus();
        $patronus->setIssuedAt(new \DateTimeImmutable());
        if ($expiresAt !== null) {
            $patronus->setExpiresAt($expiresAt);
        }
        return $patronus;
    }

    public function testPatronusIsValidReturnsTrue(): void
    {
        $patronus = $this->createPatronus(new \DateTimeImmutable('+24 hours'));

        $this->assertTrue($patronus->isValid());
    }

    public function testPatronusIsValidReturnsFalse(): void
    {
        $patronus = $this->createPatronus(new \DateTimeImmutable('-1 hour'));

        $this->assertFalse($patronus->isValid());
    }

    public function testPatronusIsExpiredReturnsTrue(): void
    {
        $patronus = $this->createPatronus(new \DateTimeImmutable('-1 hour'));

        $this->assertTrue($patronus->isExpired());
    }

    public function testPatronusIsExpiredReturnsFalse(): void
    {
        $patronus = $this->createPatronus(new \DateTimeImmutable('+24 hours'));

        $this->assertFalse($patronus->isExpired());
    }

    public function testPatronusGetToken(): void
    {
        $patronus = $this->createPatronus();

        $this->assertNotEmpty($patronus->getToken());
        $this->assertIsString($patronus->getToken());
    }

    public function testPatronusGetIssuedAt(): void
    {
        $patronus = $this->createPatronus();

        $this->assertInstanceOf(\DateTimeInterface::class, $patronus->getIssuedAt());
    }

    public function testPatronusGetExpiresAt(): void
    {
        $expiresAt = new \DateTimeImmutable('+48 hours');
        $patronus = $this->createPatronus($expiresAt);

        $this->assertSame($expiresAt, $patronus->getExpiresAt());
    }

    public function testPatronusSetUser(): void
    {
        $patronus = $this->createPatronus();
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('password123');

        $patronus->setUser($user);

        $this->assertSame($user, $patronus->getUser());
    }

    public function testPatronusSetRole(): void
    {
        $patronus = $this->createPatronus();
        $role = new Role();
        $role->setName(Role::WIZARD);

        $patronus->setRole($role);

        $this->assertSame($role, $patronus->getRole());
    }

    public function testPatronusSetRoleToNull(): void
    {
        $patronus = $this->createPatronus();
        $role = new Role();
        $role->setName(Role::WIZARD);

        $patronus->setRole($role);
        $patronus->setRole(null);

        $this->assertNull($patronus->getRole());
    }

    public function testPatronusSetTeam(): void
    {
        $patronus = $this->createPatronus();
        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $patronus->setTeam($team);

        $this->assertSame($team, $patronus->getTeam());
    }

    public function testPatronusSetTeamToNull(): void
    {
        $patronus = $this->createPatronus();
        $team = new Team();
        $team->setName('Level Design');
        $team->setCreatedAt(new \DateTimeImmutable());

        $patronus->setTeam($team);
        $patronus->setTeam(null);

        $this->assertNull($patronus->getTeam());
    }

    public function testPatronusSetToken(): void
    {
        $patronus = $this->createPatronus();

        $patronus->setToken('custom-token-123');

        $this->assertSame('custom-token-123', $patronus->getToken());
    }

    public function testPatronusSetIssuedAt(): void
    {
        $patronus = $this->createPatronus();
        $newDate = new \DateTimeImmutable('2020-01-01');

        $patronus->setIssuedAt($newDate);

        $this->assertSame($newDate, $patronus->getIssuedAt());
    }

    public function testPatronusSetExpiresAt(): void
    {
        $patronus = $this->createPatronus();
        $newDate = new \DateTimeImmutable('+7 days');

        $patronus->setExpiresAt($newDate);

        $this->assertSame($newDate, $patronus->getExpiresAt());
    }

    public function testPatronusIdIsNullInitially(): void
    {
        $patronus = $this->createPatronus();

        $this->assertNull($patronus->getId());
    }

    public function testPatronusTokenIsUnique(): void
    {
        $patronus1 = new Patronus();
        $patronus2 = new Patronus();

        $this->assertNotSame($patronus1->getToken(), $patronus2->getToken());
    }

    public function testPatronusDefaultExpirationIs24Hours(): void
    {
        $patronus = new Patronus();

        $diff = $patronus->getExpiresAt()->getTimestamp() - $patronus->getIssuedAt()->getTimestamp();

        $this->assertGreaterThanOrEqual(86399, $diff);
        $this->assertLessThanOrEqual(86401, $diff);
    }
}
