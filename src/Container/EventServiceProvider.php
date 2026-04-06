<?php

declare(strict_types=1);

namespace App\Container;

use App\Event\AsyncEventBus;
use App\Event\DoctrineEventSubscriber;
use App\Event\DomainEventEmitter;
use App\Event\ORMEventListener;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        EventDispatcherInterface::class,
        DomainEventEmitter::class,
        AsyncEventBus::class,
        DoctrineEventSubscriber::class,
        ORMEventListener::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(EventDispatcherInterface::class, function () {
            return new EventDispatcher();
        });

        $container->addShared(AsyncEventBus::class);

        $container->addShared(DomainEventEmitter::class);

        $container->addShared(DoctrineEventSubscriber::class);

        $container->add(ORMEventListener::class);
    }
}
