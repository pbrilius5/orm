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
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

class GroupActionTest extends TestCase
{
    public function testListActionReturnsHalJson(): void
    {
        $action = $this->createActionMock(ListAction::class);
        $request = new ServerRequest('GET', '/');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testShowActionWithInvalidIdReturns400(): void
    {
        $action = $this->createActionMock(ShowAction::class);

        $request = new ServerRequest('GET', '/');
        $request = $request->withAttribute('id', 'invalid');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testShowActionWithNotFoundGroupReturns404(): void
    {
        $action = $this->createActionMock(ShowAction::class);

        $request = new ServerRequest('GET', '/');
        $request = $request->withAttribute('id', '550e8400-e29b-41d4-a716-446655440000');
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testCreateActionWithoutNameReturns422(): void
    {
        $action = $this->createActionMock(CreateAction::class);

        $request = new ServerRequest('POST', '/api/groups');
        $request = $request->withMethod('POST');
        $request = $request->withBody(Utils::streamFor(json_encode([])));
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testUpdateActionWithInvalidIdReturns400(): void
    {
        $action = $this->createActionMock(UpdateAction::class);

        $request = new ServerRequest('PUT', '/api/groups/invalid');
        $request = $request->withMethod('PUT');
        $request = $request->withAttribute('id', 'invalid');
        $request = $request->withBody(Utils::streamFor(json_encode(['name' => 'Test'])));
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testPatchActionWithEmptyBodyReturns422(): void
    {
        $action = $this->createActionMock(PatchAction::class);

        $request = new ServerRequest('PATCH', '/api/groups/550e8400-e29b-41d4-a716-446655440000');
        $request = $request->withMethod('PATCH');
        $request = $request->withAttribute('id', '550e8400-e29b-41d4-a716-446655440000');
        $request = $request->withBody(Utils::streamFor('{}'));
        $response = new Response();

        $result = $action($request, $response);

        $this->assertEquals(422, $result->getStatusCode());
    }

    public function testDeleteActionWithInvalidIdReturns400(): void
    {
        $action = $this->createActionMock(DeleteAction::class);

        $request = new ServerRequest('DELETE', '/api/groups/invalid');
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
