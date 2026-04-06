<?php

declare(strict_types=1);

namespace App\Event;

class EntityUpdated extends DomainEvent
{
    private string $entityClass;
    private mixed $entityId;
    private array $changes;

    public function __construct(string $entityClass, mixed $entityId, array $changes = [])
    {
        parent::__construct();
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->changes = $changes;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): mixed
    {
        return $this->entityId;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }
}
