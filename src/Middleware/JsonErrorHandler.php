<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Oryx\Adr\Responder\ProblemDetailsResponder;

class JsonErrorHandler implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private bool $isDebug;

    private const ACCEPT_JSON = 'application/json';
    private const ACCEPT_PROBLEM = 'application/problem+json';
    private const X_REQUESTED_WITH = 'XMLHttpRequest';

    public function __construct(LoggerInterface $logger, bool $isDebug = false)
    {
        $this->logger = $logger;
        $this->isDebug = $isDebug;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid argument: ' . $e->getMessage());

            return $this->createProblemResponse(
                '/errors/bad-request',
                'Bad Request',
                400,
                $e->getMessage(),
                (string) $request->getUri()
            );
        } catch (\OutOfBoundsException|\RangeException $e) {
            $this->logger->warning('Not found: ' . $e->getMessage());

            return $this->createProblemResponse(
                '/errors/not-found',
                'Not Found',
                404,
                $e->getMessage(),
                (string) $request->getUri()
            );
        } catch (\DomainException $e) {
            $errorType = $this->extractErrorType($e);

            if ($errorType === 'unauthorized') {
                $this->logger->warning('Unauthorized: ' . $e->getMessage());

                return $this->createProblemResponse(
                    '/errors/unauthorized',
                    'Unauthorized',
                    401,
                    $e->getMessage(),
                    (string) $request->getUri()
                );
            }

            if ($errorType === 'forbidden') {
                $this->logger->warning('Forbidden: ' . $e->getMessage());

                return $this->createProblemResponse(
                    '/errors/forbidden',
                    'Forbidden',
                    403,
                    $e->getMessage(),
                    (string) $request->getUri()
                );
            }

            $this->logger->error('Domain error: ' . $e->getMessage());

            return $this->createProblemResponse(
                '/errors/domain-error',
                'Unprocessable Entity',
                422,
                $e->getMessage(),
                (string) $request->getUri()
            );
        } catch (\RuntimeException $e) {
            $this->logger->error('Runtime error: ' . $e->getMessage());

            return $this->createProblemResponse(
                '/errors/runtime-error',
                'Internal Server Error',
                500,
                $this->isDebug ? $e->getMessage() : 'An error occurred',
                (string) $request->getUri()
            );
        } catch (\Throwable $e) {
            $this->logger->critical('Critical error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createProblemResponse(
                '/errors/internal-error',
                'Internal Server Error',
                500,
                $this->isDebug ? $e->getMessage() : 'A critical error occurred',
                (string) $request->getUri(),
                ['trace' => $this->isDebug ? $e->getTraceAsString() : null]
            );
        }
    }

    private function extractErrorType(\DomainException $e): string
    {
        $message = $e->getMessage();

        if (stripos($message, 'unauthorized') !== false || stripos($message, 'authentication') !== false) {
            return 'unauthorized';
        }

        if (stripos($message, 'forbidden') !== false || stripos($message, 'access denied') !== false) {
            return 'forbidden';
        }

        return 'validation';
    }

    private function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');
        if ($accept === '') {
            return true;
        }

        $xRequestedWith = $request->getHeaderLine('X-Requested-With');
        if ($xRequestedWith === self::X_REQUESTED_WITH) {
            return true;
        }

        $mediaTypes = array_map('trim', explode(',', $accept));
        foreach ($mediaTypes as $mediaType) {
            $mediaPart = explode(';', $mediaType)[0];
            if (in_array($mediaPart, [self::ACCEPT_JSON, self::ACCEPT_PROBLEM, '*/*'], true)) {
                return true;
            }
        }

        return false;
    }

    private function createProblemResponse(
        string $type,
        string $title,
        int $status,
        string $detail,
        string $instance,
        array $extensions = []
    ): ResponseInterface {
        $extensions = array_filter($extensions, fn($v) => $v !== null);

        $responder = new ProblemDetailsResponder(
            $type,
            $title,
            $status,
            $detail,
            $instance,
            $extensions
        );

        return $responder->respond();
    }
}
