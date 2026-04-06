<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Event\DomainEvent;
use App\Event\EntityCreated;
use App\Event\EntityUpdated;
use App\Event\EntityDeleted;
use App\Logger\LoggerFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

$payload = $argv[1] ?? null;

if ($payload === null) {
    exit(1);
}

try {
    $event = unserialize(base64_decode($payload, true));

    if (!$event instanceof DomainEvent) {
        exit(1);
    }
} catch (\Throwable $e) {
    exit(1);
}

$logger = LoggerFactory::create('dev');
$asyncLogger = LoggerFactory::createAsyncFailureLogger();

$dispatcher = new EventDispatcher();

$eventName = 'async.' . get_class($event);

try {
    $dispatcher->dispatch($event, $eventName);
} catch (\Throwable $e) {
    $asyncLogger->error('Async event handler failed', [
        'event' => get_class($event),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    exit(1);
}

exit(0);
