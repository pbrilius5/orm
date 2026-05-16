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
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;
use Mockery;

class UserActionTest extends TestCase
{
    private $commandBus;
    private $dtoFactory;

    protected function setUp(): void
    {
        $this->commandBus = Mockery::mock(CommandBusInterface::class);
        $this->commandBus->shouldReceive('handle')->andReturn([]);
        $this->dtoFactory = $this->createMock(DtoFactory::class);
        $this->dtoFactory->method('create')->willReturn(new \stdClass());
    }

    public function testListActionReturnsHalJson(): void
    {
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);
        $userRepository->method('countAll')->willReturn(0);

        $action = new ListAction($this->commandBus, $this->dtoFactory, $userRepository);
        $request = new ServerRequest('GET', '/');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testShowActionWithInvalidIdReturns400(): void
    {
        $action = new ShowAction($this->commandBus, $this->dtoFactory);

        $request = new ServerRequest('GET', '/');
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testCreateActionWithoutEmailReturns422(): void
    {
        $formProcessor = $this->createMock(\App\Service\FormProcessor::class);
        $formProcessor->method('validateOrThrow')
            ->willThrowException(new \App\Exception\ValidationException([
                ['field' => 'email', 'message' => 'Email is required'],
            ]));

        $action = new CreateAction($this->commandBus, $this->dtoFactory, $formProcessor, null);

        $request = new ServerRequest('POST', '/api/users');
        $request = $request->withMethod('POST');
        $request = $request->withBody(Utils::streamFor(json_encode(['password' => 'secret123'])));
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testUpdateActionWithInvalidIdReturns400(): void
    {
        $action = new UpdateAction($this->commandBus, $this->dtoFactory, $this->createMock(\App\Service\FormProcessor::class), null);

        $request = new ServerRequest('PUT', '/api/users/invalid');
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', 'invalid');
        $request = $request->withBody(Utils::streamFor(json_encode(['email' => 'test@test.com'])));
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testPatchActionWithEmptyBodyReturns422(): void
    {
        $action = new PatchAction($this->commandBus, $this->dtoFactory, $this->createMock(\App\Service\FormProcessor::class), null);

        $request = new ServerRequest('PATCH', '/api/users/550e8400-e29b-41d4-a716-446655440000');
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '550e8400-e29b-41d4-a716-446655440000');
        $request = $request->withBody(Utils::streamFor('{}'));
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testDeleteActionWithInvalidIdReturns400(): void
    {
        $action = new DeleteAction($this->commandBus);

        $request = new ServerRequest('DELETE', '/api/users/invalid');
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }
}
