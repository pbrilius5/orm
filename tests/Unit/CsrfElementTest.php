<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Form\CsrfElement;
use PHPUnit\Framework\TestCase;

class CsrfElementTest extends TestCase
{
    private int $tokenCounter = 0;

    protected function setUp(): void
    {
        $this->tokenCounter = 0;
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function createCsrfElement(?\Closure $tokenGenerator = null): CsrfElement
    {
        $element = new CsrfElement('csrf');

        if ($tokenGenerator !== null) {
            $element->setTokenGenerator($tokenGenerator);
        } else {
            $element->setTokenGenerator(function (): string {
                $this->tokenCounter++;
                return 'test_token_' . $this->tokenCounter;
            });
        }

        return $element;
    }

    public function testImplementsElementInterface(): void
    {
        $element = $this->createCsrfElement();
        $this->assertInstanceOf(\Laminas\Form\ElementInterface::class, $element);
    }

    public function testGetNameReturnsCorrectName(): void
    {
        $element = $this->createCsrfElement();
        $this->assertEquals('csrf', $element->getName());
    }

    public function testSetNameUpdatesName(): void
    {
        $element = $this->createCsrfElement();
        $element->setName('new_csrf');
        $this->assertEquals('new_csrf', $element->getName());
    }

    public function testGenerateTokenCreatesValidHexString(): void
    {
        $element = new CsrfElement('csrf');
        $element->setTokenGenerator(function (): string {
            return bin2hex(random_bytes(16));
        });

        $token = $element->getValue();

        $this->assertIsString($token);
        $this->assertEquals(32, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function testGetValueReturnsStoredValueWhenSet(): void
    {
        $element = $this->createCsrfElement();
        $element->setValue('custom_token');

        $this->assertEquals('custom_token', $element->getValue());
    }

    public function testGetHashReturnsStringValue(): void
    {
        $element = $this->createCsrfElement();
        $hash = $element->getHash();

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function testGetAttributesIncludesTypeHidden(): void
    {
        $element = $this->createCsrfElement();
        $element->getValue();

        $attributes = $element->getAttributes();

        $this->assertArrayHasKey('type', $attributes);
        $this->assertEquals('hidden', $attributes['type']);
    }

    public function testGetAttributesIncludesName(): void
    {
        $element = $this->createCsrfElement();
        $element->getValue();

        $attributes = $element->getAttributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertEquals('csrf', $attributes['name']);
    }

    public function testGetAttributesIncludesValue(): void
    {
        $element = $this->createCsrfElement();
        $token = $element->getValue();

        $attributes = $element->getAttributes();

        $this->assertArrayHasKey('value', $attributes);
    }

    public function testSetOptionsStoresOptions(): void
    {
        $element = $this->createCsrfElement();
        $element->setOptions(['timeout' => 600]);

        $options = $element->getOptions();
        $this->assertArrayHasKey('timeout', $options);
        $this->assertEquals(600, $options['timeout']);
    }

    public function testSetOptionStoresSingleOption(): void
    {
        $element = $this->createCsrfElement();
        $element->setOption('timeout', 600);

        $this->assertEquals(600, $element->getOption('timeout'));
    }

    public function testGetOptionReturnsNullForUnknownOption(): void
    {
        $element = $this->createCsrfElement();

        $this->assertNull($element->getOption('unknown'));
    }

    public function testSetAttributeStoresAttribute(): void
    {
        $element = $this->createCsrfElement();
        $element->setAttribute('id', 'csrf-input');

        $this->assertEquals('csrf-input', $element->getAttribute('id'));
    }

    public function testGetAttributeReturnsNullForUnknownAttribute(): void
    {
        $element = $this->createCsrfElement();

        $this->assertNull($element->getAttribute('unknown'));
    }

    public function testHasAttributeReturnsFalseForUnknown(): void
    {
        $element = $this->createCsrfElement();

        $this->assertFalse($element->hasAttribute('unknown'));
    }

    public function testHasAttributeReturnsTrueForSetAttribute(): void
    {
        $element = $this->createCsrfElement();
        $element->setAttribute('id', 'test');

        $this->assertTrue($element->hasAttribute('id'));
    }

    public function testSetAttributesStoresMultipleAttributes(): void
    {
        $element = $this->createCsrfElement();
        $element->setAttributes(['id' => 'test-id', 'class' => 'test-class']);

        $this->assertEquals('test-id', $element->getAttribute('id'));
        $this->assertEquals('test-class', $element->getAttribute('class'));
    }

    public function testSetLabelReturnsSelf(): void
    {
        $element = $this->createCsrfElement();
        $result = $element->setLabel('CSRF');

        $this->assertSame($element, $result);
    }

    public function testGetLabelReturnsEmptyString(): void
    {
        $element = $this->createCsrfElement();

        $this->assertEquals('', $element->getLabel());
    }

    public function testGetMessagesReturnsEmptyArray(): void
    {
        $element = $this->createCsrfElement();

        $this->assertEquals([], $element->getMessages());
    }

    public function testSetMessagesReturnsSelf(): void
    {
        $element = $this->createCsrfElement();
        $result = $element->setMessages(['error' => 'some error']);

        $this->assertSame($element, $result);
    }

    public function testTokenGeneratorIsUsedForTokenGeneration(): void
    {
        $element = new CsrfElement('csrf');

        $called = false;
        $element->setTokenGenerator(function () use (&$called): string {
            $called = true;
            return 'custom_token';
        });

        $token = $element->getValue();

        $this->assertTrue($called);
        $this->assertEquals('custom_token', $token);
    }

    public function testMultipleCallsGenerateDifferentTokensByDefault(): void
    {
        $element = new CsrfElement('csrf');
        $element->setTokenGenerator(function (): string {
            return bin2hex(random_bytes(16));
        });

        $token1 = $element->getValue();
        $element->setValue('');
        $token2 = $element->getValue();

        $this->assertNotEquals($token1, $token2);
    }
}
