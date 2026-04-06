<?php

declare(strict_types=1);

namespace App\Event;

class EntityCreated extends DomainEvent
{
    private string $entityClass;
    private mixed $entityId;
    private array $data;

    public function __construct(string $entityClass, mixed $entityId, array $data = [])
    {
        parent::__construct();
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->data = $data;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): mixed
    {
        return $this->entityId;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
