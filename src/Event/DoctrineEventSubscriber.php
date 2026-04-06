<?php

declare(strict_types=1);

namespace App\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Ramsey\Uuid\UuidInterface;

class DoctrineEventSubscriber implements EventSubscriber
{
    private DomainEventEmitter $emitter;
    private array $pendingChanges = [];

    public function __construct(DomainEventEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::preRemove,
            Events::postRemove,
        ];
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();
        $entityClass = get_class($entity);
        $entityId = $this->getEntityId($entity);
        $data = $this->extractEntityData($entity);

        $domainEvent = new EntityCreated($entityClass, $entityId, $data);
        $this->emitter->emit($domainEvent);
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        $entityId = $this->getEntityId($entity);
        $key = spl_object_id($entity);
        $this->pendingChanges[$key] = $event->getEntityChangeSet();
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        $entityClass = get_class($entity);
        $entityId = $this->getEntityId($entity);
        $key = spl_object_id($entity);
        $changes = $this->pendingChanges[$key] ?? [];
        unset($this->pendingChanges[$key]);

        $domainEvent = new EntityUpdated($entityClass, $entityId, $changes);
        $this->emitter->emit($domainEvent);
    }

    public function preRemove(PreRemoveEventArgs $event): void {}

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $entity = $event->getObject();
        $entityClass = get_class($entity);
        $entityId = $this->getEntityId($entity);

        $domainEvent = new EntityDeleted($entityClass, $entityId);
        $this->emitter->emit($domainEvent);
        $this->emitter->emitAsync($domainEvent);
    }

    private function getEntityId(object $entity): mixed
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            if ($id instanceof UuidInterface) {
                return $id->toString();
            }
            return $id;
        }

        return null;
    }

    private function extractEntityData(object $entity): array
    {
        $data = [];
        $class = new \ReflectionClass($entity);

        foreach ($class->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            if ($value instanceof \DateTimeInterface) {
                $data[$property->getName()] = $value->format(\DateTimeInterface::ATOM);
            } elseif ($value instanceof UuidInterface) {
                $data[$property->getName()] = $value->toString();
            } elseif (!is_object($value)) {
                $data[$property->getName()] = $value;
            }
        }

        return $data;
    }
}
