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

class CreateAction
{
    private FixtureLoader $loader;
    private Manager $fractal;

    public function __construct(FixtureLoader $loader, Manager $fractal)
    {
        $this->loader = $loader;
        $this->fractal = $fractal;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return JsonHalResponder::badRequest('Invalid JSON in request body');
        }

        if (empty($body['email']) || empty($body['password'])) {
            return JsonHalResponder::unprocessableEntity([
                ['field' => 'email', 'message' => 'Email is required'],
                ['field' => 'password', 'message' => 'Password is required'],
            ]);
        }

        $user = $this->loader->make(User::class);
        $user->setEmail($body['email']);
        $user->setPassword(password_hash($body['password'], PASSWORD_BCRYPT));

        if (isset($body['roles'])) {
            $user->setRoles($body['roles']);
        }

        $resource = new Item($user, new \App\Transformer\Resource\UserTransformer());
        $data = $this->fractal->createData($resource)->toArray();

        return JsonHalResponder::created(
            'user',
            'new',
            $data,
            [
                'collection' => '/api/users',
                'self' => '/api/users/new',
            ]
        );
    }
}
