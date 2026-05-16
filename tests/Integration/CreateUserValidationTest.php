<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use App\Kernel;

class CreateUserValidationTest extends TestCase
{
    public function testInvalidEmailReturns422(): void
    {
        // Ensure Doctrine driver matches supported DSN names for local test
        putenv('DB_DRIVER=pdo_sqlite');
        $_ENV['DB_DRIVER'] = 'pdo_sqlite';
        $_SERVER['DB_DRIVER'] = 'pdo_sqlite';
        $kernel = new Kernel('test');

        $body = json_encode(['email' => 'not-an-email', 'password' => 'secret123', 'work_group' => 'developer']);

        $request = ServerRequest::fromGlobals()
            ->withMethod('POST')
            ->withUri(new Uri('/api/users'))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($body));

        $response = $kernel->handle($request);

        $this->assertSame(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('_error', $payload);
        $this->assertEquals('Unprocessable Entity', $payload['_error']['title']);
        $this->assertNotEmpty($payload['_error']['errors']);
        $this->assertEquals('email', $payload['_error']['errors'][0]['field']);
    }
}
