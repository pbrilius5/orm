<?php

declare(strict_types=1);

namespace App\Tests\Action;

use PHPUnit\Framework\TestCase;
use App\Action\User\ListAction;
use App\Action\User\ShowAction;
use App\Action\User\CreateAction;
use App\Action\User\UpdateAction;
use App\Action\User\PatchAction;
use App\Action\User\DeleteAction;
use App\Fixture\FixtureLoader;
use League\Fractal\Manager;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;
use Ramsey\Uuid\Uuid;

class UserActionTest extends TestCase
{
    private ListAction $listAction;
    private ShowAction $showAction;
    private CreateAction $createAction;
    private UpdateAction $updateAction;
    private PatchAction $patchAction;
    private DeleteAction $deleteAction;

    protected function setUp(): void
    {
        $loader = new FixtureLoader();
        $fractal = new Manager();

        $this->listAction = new ListAction($loader, $fractal);
        $this->showAction = new ShowAction($loader, $fractal);
        $this->createAction = new CreateAction($loader, $fractal);
        $this->updateAction = new UpdateAction($loader, $fractal);
        $this->patchAction = new PatchAction($loader, $fractal);
        $this->deleteAction = new DeleteAction();
    }

    public function testListActionReturnsHalJson(): void
    {
        $request = new ServerRequest();
        $response = new Response();

        $result = ($this->listAction)($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('_embedded', $body);
        $this->assertArrayHasKey('users', $body['_embedded']);
    }

    public function testShowActionReturnsUser(): void
    {
        $uuid = Uuid::uuid4();
        $request = new ServerRequest([], [], '/api/users/' . $uuid->toString(), 'GET');
        $request = $request->withAttribute('id', $uuid->toString());
        $response = new Response();

        $result = ($this->showAction)($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('_links', $body);
        $this->assertArrayHasKey('user', $body);
    }

    public function testShowActionWithInvalidIdReturns400(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'not-a-uuid');
        $response = new Response();

        $result = ($this->showAction)($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testCreateActionReturns201(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'roles' => ['ROLE_USER'],
        ];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = ($this->createAction)($request, $response);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
    }

    public function testCreateActionWithoutEmailReturns422(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['password' => 'secret123']));
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = ($this->createAction)($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testUpdateActionReturns200(): void
    {
        $uuid = Uuid::uuid4();
        $data = [
            'email' => 'updated@example.com',
        ];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', $uuid->toString());
        $request = $request->withBody($stream);
        $response = new Response();

        $result = ($this->updateAction)($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
    }

    public function testPatchActionReturns200(): void
    {
        $uuid = Uuid::uuid4();
        $data = [
            'email' => 'patched@example.com',
        ];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', $uuid->toString());
        $request = $request->withBody($stream);
        $response = new Response();

        $result = ($this->patchAction)($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
    }

    public function testPatchActionWithEmptyBodyReturns422(): void
    {
        $uuid = Uuid::uuid4();
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write('{}');
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', $uuid->toString());
        $request = $request->withBody($stream);
        $response = new Response();

        $result = ($this->patchAction)($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testDeleteActionReturns204(): void
    {
        $uuid = Uuid::uuid4();
        $request = new ServerRequest();
        $request = $request->withMethod('DELETE');
        $request = $request->withAttribute('id', $uuid->toString());
        $response = new Response();

        $result = ($this->deleteAction)($request, $response);

        $this->assertEquals(204, $result->getStatusCode());
    }

    public function testDeleteActionWithInvalidIdReturns400(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = ($this->deleteAction)($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }
}
