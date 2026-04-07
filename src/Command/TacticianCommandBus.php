<?php

declare(strict_types=1);

namespace App\Command;

use League\Tactician\CommandBus;

class TacticianCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    /**
     * @param object $command
     * @return mixed
     */
    public function handle($command): mixed
    {
        return $this->commandBus->handle($command);
    }
}
