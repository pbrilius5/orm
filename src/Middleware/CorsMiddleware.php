<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Oryx\Adr\Responder\JsonApiResponder;

class CorsMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct(
        LoggerInterface $logger,
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'Accept']
    ) {
        $this->logger = $logger;
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $origin = $request->getHeaderLine('Origin');
        $method = $request->getMethod();

        $this->logger->debug('CorsMiddleware processing request', [
            'method' => $method,
            'origin' => $origin ?: '(none)',
            'uri' => $request->getUri()->getPath(),
        ]);

        if ($method === 'OPTIONS') {
            $this->logger->debug('CorsMiddleware handling preflight request', [
                'origin' => $origin ?: '(none)',
                'requested_method' => $request->getHeaderLine('Access-Control-Request-Method') ?: '(none)',
            ]);

            return (new JsonApiResponder(null, 204, $this->getCorsHeaders($request)))->respond();
        }

        $response = $handler->handle($request);
        $this->logger->debug('CorsMiddleware adding CORS headers', [
            'method' => $method,
            'origin' => $origin ?: '(none)',
            'status_code' => $response->getStatusCode(),
        ]);

        return $this->addCorsHeaders($response, $request);
    }

    private function getCorsHeaders(ServerRequestInterface $request): array
    {
        $origin = $request->getHeaderLine('Origin');

        return [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigin($origin),
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Access-Control-Allow-Headers' => implode(', ', $this->allowedHeaders),
            'Access-Control-Max-Age' => '86400',
            'Access-Control-Expose-Headers' => 'X-Request-Id, X-RateLimit-Remaining',
        ];
    }

    private function addCorsHeaders(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->getAllowedOrigin($origin))
            ->withHeader('Access-Control-Expose-Headers', 'X-Request-Id, X-RateLimit-Remaining');
    }

    private function getAllowedOrigin(string $origin): string
    {
        if (in_array('*', $this->allowedOrigins)) {
            return '*';
        }

        if (in_array($origin, $this->allowedOrigins)) {
            return $origin;
        }

        return $this->allowedOrigins[0] ?? '*';
    }
}
