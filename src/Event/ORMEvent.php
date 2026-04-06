<?php

declare(strict_types=1);

namespace App\Event;

use League\Event\Event;

class ORMEvent extends Event
{
    private array $params;

    public function __construct(string $name, array $params = [])
    {
        parent::__construct($name);
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}
