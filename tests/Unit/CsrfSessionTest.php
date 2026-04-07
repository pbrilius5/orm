<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Csrf\CsrfSession;
use PHPUnit\Framework\TestCase;

class CsrfSessionTest extends TestCase
{
    private array $testSession = [];

    protected function setUp(): void
    {
        $this->testSession = [];
        CsrfSession::setSessionAccessors(
            getter: function (): array {
                return $this->testSession;
            },
            setter: function (array $session): void {
                $this->testSession = $session;
            }
        );
    }

    protected function tearDown(): void
    {
        CsrfSession::clearSessionAccessors();
    }

    public function testGenerateTokenReturns32CharHexString(): void
    {
        $token = CsrfSession::generateToken();

        $this->assertIsString($token);
        $this->assertEquals(32, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function testGenerateTokenStoresInSession(): void
    {
        $token = CsrfSession::generateToken();

        $this->assertArrayHasKey($token, $this->testSession);
    }

    public function testGenerateTokenStoresTimestamp(): void
    {
        $before = time();
        $token = CsrfSession::generateToken();
        $after = time();

        $storedTime = $this->testSession[$token];
        $this->assertGreaterThanOrEqual($before, $storedTime);
        $this->assertLessThanOrEqual($after, $storedTime);
    }

    public function testMultipleTokensAreUnique(): void
    {
        $token1 = CsrfSession::generateToken();
        $token2 = CsrfSession::generateToken();

        $this->assertNotEquals($token1, $token2);
    }

    public function testTokenLimitIsEnforced(): void
    {
        for ($i = 0; $i < 60; $i++) {
            CsrfSession::generateToken();
        }

        $this->assertLessThanOrEqual(50, count($this->testSession));
    }

    public function testValidateTokenReturnsTrueForValidToken(): void
    {
        $token = CsrfSession::generateToken();
        $result = CsrfSession::validateToken($token);

        $this->assertTrue($result);
    }

    public function testValidateTokenRemovesUsedToken(): void
    {
        $token = CsrfSession::generateToken();
        CsrfSession::validateToken($token);

        $this->assertArrayNotHasKey($token, $this->testSession);
    }

    public function testValidateTokenReturnsFalseForInvalidToken(): void
    {
        $result = CsrfSession::validateToken('invalid_token');

        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForExpiredToken(): void
    {
        $token = CsrfSession::generateToken();
        $this->testSession[$token] = time() - 4000;

        $result = CsrfSession::validateToken($token, 3600);

        $this->assertFalse($result);
    }

    public function testValidateTokenWithCustomMaxAge(): void
    {
        $token = CsrfSession::generateToken();
        $this->testSession[$token] = time() - 100;

        $result = CsrfSession::validateToken($token, 60);

        $this->assertFalse($result);
    }

    public function testValidateTokenWithValidMaxAge(): void
    {
        $token = CsrfSession::generateToken();
        $this->testSession[$token] = time() - 50;

        $result = CsrfSession::validateToken($token, 60);

        $this->assertTrue($result);
    }
}
