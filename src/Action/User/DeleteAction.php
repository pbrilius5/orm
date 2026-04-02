<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Responder\JsonHalResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeleteAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (int) ($request->getAttribute('id') ?? 0);

        if ($id <= 0) {
            return JsonHalResponder::badRequest('Invalid user ID provided');
        }

        return JsonHalResponder::noContent();
    }
}
