<?php

declare(strict_types=1);

namespace App\Tests\Action;

use PHPUnit\Framework\TestCase;
use App\Action\Group\ListAction;
use App\Action\Group\ShowAction;
use App\Action\Group\CreateAction;
use App\Action\Group\UpdateAction;
use App\Action\Group\PatchAction;
use App\Action\Group\DeleteAction;
use App\Repository\GroupRepository;
use App\Entity\Group;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;

class GroupActionTest extends TestCase
{
    private GroupRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GroupRepository::class);
    }

    private function createGroup(int $id, string $name): Group
    {
        $group = new Group();
        $group->setId($id);
        $group->setName($name);
        $group->setCreatedAt(new \DateTimeImmutable());

        return $group;
    }

    public function testListActionReturnsHalJson(): void
    {
        $groups = [
            $this->createGroup(1, 'Developers'),
            $this->createGroup(2, 'Designers'),
        ];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($groups);

        $listAction = new ListAction($this->repository);
        $request = new ServerRequest();
        $response = new Response();

        $result = $listAction($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('_embedded', $body);
        $this->assertArrayHasKey('groups', $body['_embedded']);
        $this->assertCount(2, $body['_embedded']['groups']);
    }

    public function testListActionReturnsEmptyCollectionWhenNoGroups(): void
    {
        $this->repository->method('findAll')->willReturn([]);

        $listAction = new ListAction($this->repository);
        $request = new ServerRequest();
        $response = new Response();

        $result = $listAction($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertIsArray($body['_embedded']['groups']);
        $this->assertCount(0, $body['_embedded']['groups']);
    }

    public function testShowActionReturnsGroup(): void
    {
        $group = $this->createGroup(1, 'Developers');
        $this->repository->method('find')->with(1)->willReturn($group);

        $showAction = new ShowAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', '1');
        $response = new Response();

        $result = $showAction($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('_links', $body);
        $this->assertArrayHasKey('group', $body);
        $this->assertEquals('Developers', $body['group']['data']['name']);
    }

    public function testShowActionWithInvalidIdReturns400(): void
    {
        $showAction = new ShowAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $showAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testShowActionWithZeroIdReturns400(): void
    {
        $showAction = new ShowAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', '0');
        $response = new Response();

        $result = $showAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testShowActionWithNegativeIdReturns400(): void
    {
        $showAction = new ShowAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', '-1');
        $response = new Response();

        $result = $showAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testShowActionWithNotFoundGroupReturns404(): void
    {
        $this->repository->method('find')->with(999)->willReturn(null);

        $showAction = new ShowAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', '999');
        $response = new Response();

        $result = $showAction($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testCreateActionReturns201(): void
    {
        $data = ['name' => 'Test Group'];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $createAction = new CreateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $createAction($request, $response);

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
    }

    public function testCreateActionWithoutNameReturns422(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode([]));
        $stream->rewind();

        $createAction = new CreateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $createAction($request, $response);

        $this->assertEquals(422, $result->getStatusCode());

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('_error', $body);
    }

    public function testCreateActionWithInvalidJsonReturns400(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write('not json');
        $stream->rewind();

        $createAction = new CreateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $createAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testCreateActionWithNameReturnsValidData(): void
    {
        $data = ['name' => 'Developers'];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $createAction = new CreateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $createAction($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('group', $body);
        $this->assertEquals('Developers', $body['group']['data']['name']);
    }

    public function testUpdateActionReturns200(): void
    {
        $group = $this->createGroup(1, 'Developers');
        $this->repository->method('find')->with(1)->willReturn($group);

        $data = ['name' => 'Updated Group'];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $updateAction = new UpdateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', '1');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $updateAction($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
    }

    public function testUpdateActionWithInvalidIdReturns400(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['name' => 'Test']));
        $stream->rewind();

        $updateAction = new UpdateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', 'invalid');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $updateAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testUpdateActionWithInvalidJsonReturns400(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write('not json');
        $stream->rewind();

        $updateAction = new UpdateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', '1');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $updateAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testUpdateActionWithNotFoundGroupReturns404(): void
    {
        $this->repository->method('find')->with(999)->willReturn(null);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['name' => 'Test']));
        $stream->rewind();

        $updateAction = new UpdateAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', '999');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $updateAction($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testPatchActionReturns200(): void
    {
        $group = $this->createGroup(1, 'Developers');
        $this->repository->method('find')->with(1)->willReturn($group);

        $data = ['name' => 'Patched Group'];

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode($data));
        $stream->rewind();

        $patchAction = new PatchAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '1');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $patchAction($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/hal+json', $result->getHeaderLine('Content-Type'));
    }

    public function testPatchActionWithEmptyBodyReturns422(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write('{}');
        $stream->rewind();

        $patchAction = new PatchAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '1');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $patchAction($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testPatchActionWithInvalidIdReturns400(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['name' => 'Test']));
        $stream->rewind();

        $patchAction = new PatchAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', 'invalid');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $patchAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testPatchActionWithInvalidJsonReturns400(): void
    {
        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write('not json');
        $stream->rewind();

        $patchAction = new PatchAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '1');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $patchAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testPatchActionWithNotFoundGroupReturns404(): void
    {
        $this->repository->method('find')->with(999)->willReturn(null);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['name' => 'Test']));
        $stream->rewind();

        $patchAction = new PatchAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '999');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $patchAction($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testDeleteActionReturns204(): void
    {
        $group = $this->createGroup(1, 'Developers');
        $this->repository->method('find')->with(1)->willReturn($group);

        $deleteAction = new DeleteAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withMethod('DELETE');
        $request = $request->withAttribute('id', '1');
        $response = new Response();

        $result = $deleteAction($request, $response);

        $this->assertEquals(204, $result->getStatusCode());
    }

    public function testDeleteActionWithInvalidIdReturns400(): void
    {
        $deleteAction = new DeleteAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $deleteAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testDeleteActionWithZeroIdReturns400(): void
    {
        $deleteAction = new DeleteAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', '0');
        $response = new Response();

        $result = $deleteAction($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testDeleteActionWithNotFoundGroupReturns404(): void
    {
        $this->repository->method('find')->with(999)->willReturn(null);

        $deleteAction = new DeleteAction($this->repository);
        $request = new ServerRequest();
        $request = $request->withAttribute('id', '999');
        $response = new Response();

        $result = $deleteAction($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
    }
}
