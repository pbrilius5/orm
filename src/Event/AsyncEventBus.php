<?php

declare(strict_types=1);

namespace App\Event;

use League\Event\EmitterInterface;
use Psr\Log\LoggerInterface;

class AsyncEventBus
{
    private LoggerInterface $logger;
    private string $workerPath;

    public function __construct(LoggerInterface $logger, ?string $workerPath = null)
    {
        $this->logger = $logger;
        $this->workerPath = $workerPath ?? dirname(__DIR__, 2) . '/bin/async-event-worker.php';
    }

    public function dispatch(DomainEvent $event): void
    {
        $payload = base64_encode(serialize($event));

        $command = sprintf(
            'php %s %s > /dev/null 2>&1 &',
            escapeshellarg($this->workerPath),
            escapeshellarg($payload)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->logger->error('Failed to spawn async event worker', [
                'event' => get_class($event),
                'exit_code' => $exitCode,
                'command' => $command,
            ]);
        }
    }
}
