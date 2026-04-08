<?php

declare(strict_types=1);

namespace App\ErrorHandler;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run as WhoopsRun;

class MvcErrorHandler
{
    private WhoopsRun $whoops;
    private bool $isDebug;
    private bool $registered = false;

    public function __construct(bool $isDebug = true)
    {
        $this->isDebug = $isDebug;
    }

    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->whoops = new WhoopsRun();

        if ($this->isDebug) {
            $this->whoops->pushHandler(new PrettyPageHandler());
        } else {
            $this->whoops->pushHandler(new JsonResponseHandler());
        }

        $this->whoops->register();
        $this->registered = true;
    }
}
