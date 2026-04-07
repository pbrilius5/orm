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
use App\Dto\DtoFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;

class GroupActionTest extends TestCase
{
    public function testListActionReturnsHalJson(): void
    {
        $action = $this->createActionMock(ListAction::class);
        $request = new ServerRequest();
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testShowActionWithInvalidIdReturns400(): void
    {
        $action = $this->createActionMock(ShowAction::class);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testShowActionWithNotFoundGroupReturns404(): void
    {
        $action = $this->createActionMock(ShowAction::class);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', '550e8400-e29b-41d4-a716-446655440000');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testCreateActionWithoutNameReturns422(): void
    {
        $action = $this->createActionMock(CreateAction::class);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode([]));
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('POST');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testUpdateActionWithInvalidIdReturns400(): void
    {
        $action = $this->createActionMock(UpdateAction::class);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['name' => 'Test']));
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', 'invalid');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testPatchActionWithEmptyBodyReturns422(): void
    {
        $action = $this->createActionMock(PatchAction::class);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write('{}');
        $stream->rewind();

        $request = new ServerRequest();
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '550e8400-e29b-41d4-a716-446655440000');
        $request = $request->withBody($stream);
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testDeleteActionWithInvalidIdReturns400(): void
    {
        $action = $this->createActionMock(DeleteAction::class);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    private function createActionMock(string $actionClass)
    {
        $dtoFactory = $this->createMock(DtoFactory::class);
        $dtoFactory->method('create')->willReturn(new \stdClass());

        $groupRepository = $this->createMock(\App\Repository\GroupRepository::class);
        $groupRepository->method('countAll')->willReturn(0);

        return new $actionClass(
            new class implements \App\Command\CommandBusInterface {
                public function handle($command)
                {
                    $class = get_class($command);
                    if (strpos($class, 'ListGroupsCommand') !== false) {
                        return [];
                    }
                    return null;
                }
            },
            $dtoFactory,
            $groupRepository
        );
    }
}
