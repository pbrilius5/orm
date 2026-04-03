<?php

declare(strict_types=1);

namespace App\Command;

interface CommandBusInterface
{
    /**
     * @param object $command
     * @return mixed
     */
    public function handle($command);
}
