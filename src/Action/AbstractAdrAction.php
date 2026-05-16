<?php

declare(strict_types=1);

namespace App\Action;

use Oryx\Adr\Action\ActionInterface;
use Oryx\Adr\Domain\DomainInterface;
use Oryx\Adr\Responder\JsonApiResponder;

abstract class AbstractAdrAction implements ActionInterface
{
    public function execute(DomainInterface $domain): string
    {
        return JsonApiResponder::class;
    }
}
