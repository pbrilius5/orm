<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Oryx\Adr\Responder\JsonApiResponder;

/**
 * Rate limiting middleware with Memcached support.
 * Falls back to array-based storage when Memcached is not available.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private int $maxRequests;
    private int $windowSeconds;
    private ?\Memcached $memcached = null;
    private array $fallbackStorage = [];

    public function __construct(LoggerInterface $logger, int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->logger = $logger;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;

        // Try to initialize Memcached if extension is available
        if (class_exists('Memcached')) {
            $this->memcached = new \Memcached();
            $servers = $this->memcached->getServerList();

            if (empty($servers)) {
                // Try to connect to default Memcached instance
                @$this->memcached->addServer('localhost', 11211);

                // Verify connection
                if ($this->memcached->getServerList() === []) {
                    $this->memcached = null; // Disable Memcached if connection failed
                }
            }
        }
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $ip = $request->getHeaderLine('X-Forwarded-For')
            ?: $request->getHeaderLine('X-Real-IP')
            ?: $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();

        $this->logger->debug('RateLimitMiddleware checking rate limit', [
            'ip' => $ip,
            'uri' => $uri,
            'method' => $method,
            'limit' => $this->maxRequests,
            'window' => $this->windowSeconds,
        ]);

        $key = 'rate_limit:' . $this->getKey($request);
        $now = time();
        $windowKey = $key . ':window';
        $countKey = $key . ':count';

        if ($this->memcached !== null) {
            // Use Memcached for storage
            $windowStart = $this->memcached->get($windowKey);
            $count = (int) $this->memcached->get($countKey);

            if ($windowStart === false || $now - $windowStart >= $this->windowSeconds) {
                // Window expired, reset counters
                $windowStart = $now;
                $count = 0;
                $this->memcached->set($windowKey, $windowStart, $this->windowSeconds * 2); // Expire window after 2x window time
                $this->memcached->set($countKey, $count, $this->windowSeconds * 2);
            }

            $count++;
            $remaining = max(0, $this->maxRequests - $count);

            // Store updated count with appropriate expiration
            $this->memcached->set($countKey, $count, $this->windowSeconds);

            if ($count > $this->maxRequests) {
                $this->logger->warning('Rate limit exceeded', [
                    'ip' => $ip,
                    'uri' => $uri,
                    'method' => $method,
                    'count' => $count,
                    'limit' => $this->maxRequests,
                ]);

                return $this->createRateLimitExceededResponse();
            }
        } else {
            // Fallback to array-based storage
            if (!isset($this->fallbackStorage[$key])) {
                $this->fallbackStorage[$key] = ['count' => 0, 'window_start' => $now];
            }

            $record = &$this->fallbackStorage[$key];

            if ($now - $record['window_start'] >= $this->windowSeconds) {
                $record = ['count' => 0, 'window_start' => $now];
            }

            $record['count']++;
            $remaining = max(0, $this->maxRequests - $record['count']);

            if ($record['count'] > $this->maxRequests) {
                $this->logger->warning('Rate limit exceeded', [
                    'ip' => $ip,
                    'uri' => $uri,
                    'method' => $method,
                    'count' => $record['count'],
                    'limit' => $this->maxRequests,
                ]);

                return $this->createRateLimitExceededResponse();
            }
        }

        $this->logger->debug('RateLimitMiddleware allowing request', [
            'ip' => $ip,
            'uri' => $uri,
            'method' => $method,
            'count' => $this->memcached !== null ? (int) $this->memcached->get($countKey) : $this->fallbackStorage[$key]['count'] ?? 0,
            'limit' => $this->maxRequests,
            'remaining' => $remaining,
        ]);

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }

    private function getKey(ServerRequestInterface $request): string
    {
        $ip = $request->getHeaderLine('X-Forwarded-For')
            ?: $request->getHeaderLine('X-Real-IP')
            ?: 'unknown';

        return $ip;
    }

    public function reset(string $key): void
    {
        $key = 'rate_limit:' . $key;
        $windowKey = $key . ':window';
        $countKey = $key . ':count';

        if ($this->memcached !== null) {
            $this->memcached->delete($windowKey);
            $this->memcached->delete($countKey);
        } else {
            unset($this->fallbackStorage[$key]);
        }
    }

    public function resetAll(): void
    {
        if ($this->memcached !== null) {
            // Note: This is a simplified approach. In production, you might want to use
            // a specific key prefix and flush only those keys, or use flush() if appropriate.
            $this->memcached->flush();
        } else {
            $this->fallbackStorage = [];
        }
    }

    private function createRateLimitExceededResponse(): ResponseInterface
    {
        return (new JsonApiResponder([
            '_error' => [
                'status' => 429,
                'title' => 'Too Many Requests',
                'detail' => "Rate limit exceeded. Try again in {$this->windowSeconds} seconds.",
            ],
        ], 429, [
            'Content-Type' => 'application/hal+json; charset=utf-8',
            'Retry-After' => (string) $this->windowSeconds,
            'X-RateLimit-Limit' => (string) $this->maxRequests,
            'X-RateLimit-Remaining' => '0',
        ]))->respond();
    }
}
