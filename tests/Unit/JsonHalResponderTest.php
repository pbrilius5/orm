<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Responder\JsonHalResponder;
use Oryx\Adr\Responder\JsonApiResponder;
use Oryx\Adr\Responder\ResponderInterface;
use PHPUnit\Framework\TestCase;

class JsonHalResponderTest extends TestCase
{
    public function testJsonHalResponderInheritsVendorResponderContracts(): void
    {
        $this->assertTrue(is_subclass_of(JsonHalResponder::class, JsonApiResponder::class));
        $this->assertTrue(is_subclass_of(JsonHalResponder::class, ResponderInterface::class));
    }

    public function testResourceResponseUsesHalMediaType(): void
    {
        $response = JsonHalResponder::resource('user', '42', ['email' => 'john@example.com']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('application/hal+json', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertSame('/user/42', $payload['_links']['self']['href']);
        $this->assertSame('42', $payload['user']['id']);
        $this->assertSame('john@example.com', $payload['user']['email']);
    }

    public function testNoContentUsesInheritedJsonApiResponder(): void
    {
        $response = JsonHalResponder::noContent()->respond();

        $this->assertSame(204, $response->getStatusCode());
    }
}
