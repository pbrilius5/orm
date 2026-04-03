<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class DeleteAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $request->getAttribute('id') ?? '';

        if (!Uuid::isValid($id)) {
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        return JsonHalResponder::noContent();
    }
}
