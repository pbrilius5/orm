<?php

declare(strict_types=1);

namespace App\Event;

class EntityDeleted extends DomainEvent
{
    private string $entityClass;
    private mixed $entityId;

    public function __construct(string $entityClass, mixed $entityId)
    {
        parent::__construct();
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): mixed
    {
        return $this->entityId;
    }
}
