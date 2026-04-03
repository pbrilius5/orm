<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Fixture\FixtureLoader;
use App\Entity\User;
use App\Responder\JsonHalResponder;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class ShowAction
{
    private FixtureLoader $loader;
    private Manager $fractal;

    public function __construct(FixtureLoader $loader)
    {
        $this->loader = $loader;
        $this->fractal = new Manager();
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        $uuid = Uuid::fromString($id);
        $user = $this->loader->make(User::class);
        $user->setId($uuid);
        $user->setEmail("user-{$id}@example.com");

        $resource = new Item($user, new \App\Transformer\Resource\UserTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::resource(
            'user',
            $id,
            $data,
            [
                'collection' => '/api/users',
            ]
        );
    }
}
