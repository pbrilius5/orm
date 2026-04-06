<?php

declare(strict_types=1);

namespace App\Event;

use League\Event\EventInterface;
use League\Event\ListenerInterface;
use Psr\Log\LoggerInterface;

class ORMEventListener implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(EventInterface $event): void
    {
        $eventName = $event->getName();

        if (!str_starts_with($eventName, 'orm.')) {
            return;
        }

        $params = $event instanceof ORMEvent ? $event->getParams() : [];
        $entity = $params['entity'] ?? null;
        $entityClass = $entity !== null ? get_class($entity) : null;
        $entityId = $entity !== null ? $this->getEntityId($entity) : null;

        $context = [
            'event' => $eventName,
            'entity_class' => $entityClass,
            'entity_id' => $entityId,
        ];

        if (isset($params['entityName'])) {
            $context['entity_name'] = $params['entityName'];
        }

        match ($eventName) {
            'orm.prePersist' => $this->logger->debug('Entity prePersist', $context),
            'orm.postPersist' => $this->logger->info('Entity postPersist', $context),
            'orm.preFlush' => $this->logger->debug('EntityManager preFlush', $context),
            'orm.postFlush' => $this->logger->info('EntityManager postFlush', $context),
            'orm.preClear' => $this->logger->debug('EntityManager preClear', $context),
            'orm.postClear' => $this->logger->info('EntityManager postClear', $context),
            default => $this->logger->debug('ORM event', $context),
        };
    }

    public function isListener($listener): bool
    {
        return $this === $listener;
    }

    private function getEntityId(object $entity): mixed
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            if ($id instanceof \Ramsey\Uuid\UuidInterface) {
                return $id->toString();
            }
            return $id;
        }

        return null;
    }
}
