<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class SecurityMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->logger->debug('SecurityMiddleware processing request', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
        ]);

        $response = $handler->handle($request);

        $this->logger->debug('SecurityMiddleware finished processing', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'status_code' => $response->getStatusCode(),
        ]);

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }
}
