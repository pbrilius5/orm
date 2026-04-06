<?php

declare(strict_types=1);

namespace App\Event;

interface DomainEventHandler
{
    public function handle(DomainEvent $event): void;
}
