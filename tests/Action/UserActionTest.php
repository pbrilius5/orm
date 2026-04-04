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
use App\Command\CommandBusInterface;
use App\Dto\DtoFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;

class UserActionTest extends TestCase
{
    private $commandBus;
    private $dtoFactory;

    protected function setUp(): void
    {
        $this->commandBus = new class implements CommandBusInterface {
            public function handle($command)
            {
                return [];
            }
        };
        $this->dtoFactory = $this->createMock(DtoFactory::class);
        $this->dtoFactory->method('create')->willReturn(new \stdClass());
    }

    public function testListActionReturnsHalJson(): void
    {
        $action = new ListAction($this->commandBus, $this->dtoFactory);
        $request = new ServerRequest();
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testShowActionWithInvalidIdReturns400(): void
    {
        $action = new ShowAction($this->commandBus, $this->dtoFactory);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testCreateActionWithoutEmailReturns422(): void
    {
        $action = new CreateAction($this->commandBus, $this->dtoFactory);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['password' => 'secret123']));
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
        $action = new UpdateAction($this->commandBus, $this->dtoFactory);

        $stream = new \Laminas\Diactoros\Stream('php://memory', 'w+');
        $stream->write(json_encode(['email' => 'test@test.com']));
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
        $action = new PatchAction($this->commandBus, $this->dtoFactory);

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
        $action = new DeleteAction($this->commandBus);

        $request = new ServerRequest();
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }
}
