<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Csrf\CsrfSession;
use App\Responder\JsonHalResponder;

class CsrfMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private const TOKEN_NAME = '_csrf_token';
    private const HEADER_NAME = 'X-CSRF-Token';

    private array $exemptRoutes = [
        'GET' => ['/api/health', '/manifest.json', '/api/users'],
        'HEAD' => ['/api/health', '/manifest.json'],
        'OPTIONS' => ['*'],
        'POST' => ['/api/*'],
        'PUT' => ['/api/*'],
        'PATCH' => ['/api/*'],
        'DELETE' => ['/api/*'],
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $method = $request->getMethod();
        $path = parse_url((string) $request->getUri(), PHP_URL_PATH) ?? '/';

        $this->logger->debug('CsrfMiddleware processing request', [
            'method' => $method,
            'path' => $path,
            'exempt' => $this->isExempt($method, $path),
        ]);

        if ($this->isExempt($method, $path)) {
            return $handler->handle($request);
        }

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            $token = CsrfSession::generateToken();

            $response = $handler->handle($request);
            return $response->withHeader('X-CSRF-Token', $token);
        }

        $token = $this->getSubmittedToken($request);

        $this->logger->debug('CsrfMiddleware validating token', [
            'method' => $method,
            'path' => $path,
            'has_token' => (bool) $token,
        ]);

        if (!$token || !CsrfSession::validateToken($token)) {
            $this->logger->warning('CsrfMiddleware token validation failed', [
                'method' => $method,
                'path' => $path,
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            return JsonHalResponder::forbidden('CSRF token validation failed');
        }

        $this->logger->debug('CsrfMiddleware token validation passed', [
            'method' => $method,
            'path' => $path,
        ]);

        return $handler->handle($request);
    }

    private function isExempt(string $method, string $path): bool
    {
        if (!isset($this->exemptRoutes[$method])) {
            return false;
        }

        foreach ($this->exemptRoutes[$method] as $pattern) {
            if ($pattern === '*') {
                return true;
            }
            if (str_starts_with($pattern, '/') && $path === $pattern) {
                return true;
            }
            if (str_contains($pattern, '*')) {
                $regex = '#^' . str_replace('*', '.*', $pattern) . '$#';
                if (preg_match($regex, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getSubmittedToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine(self::HEADER_NAME);
        if ($header) {
            return $header;
        }

        $body = json_decode((string) $request->getBody(), true) ?? [];
        if (isset($body[self::TOKEN_NAME])) {
            return $body[self::TOKEN_NAME];
        }

        $parsed = (array) $request->getParsedBody();
        if (isset($parsed[self::TOKEN_NAME])) {
            return $parsed[self::TOKEN_NAME];
        }

        return null;
    }

    public function generateFormToken(string $sessionId): string
    {
        return CsrfSession::generateToken();
    }
}
