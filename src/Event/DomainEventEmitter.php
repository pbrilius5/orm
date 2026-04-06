<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DomainEventEmitter
{
    private EventDispatcherInterface $dispatcher;
    private AsyncEventBus $asyncBus;

    public function __construct(EventDispatcherInterface $dispatcher, AsyncEventBus $asyncBus)
    {
        $this->dispatcher = $dispatcher;
        $this->asyncBus = $asyncBus;
    }

    public function emit(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event, $event::class);
    }

    public function emitAsync(DomainEvent $event): void
    {
        $this->asyncBus->dispatch($event);
    }
}
