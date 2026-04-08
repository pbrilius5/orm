<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\ErrorHandler\MvcErrorHandler;

class MvcErrorHandlerTest extends TestCase
{
    public function testConstructorStoresIsDebug(): void
    {
        $handler = new MvcErrorHandler(true);
        $this->assertTrue($handler->isDebug());

        $handlerFalse = new MvcErrorHandler(false);
        $this->assertFalse($handlerFalse->isDebug());
    }

    public function testRegisterDoesNotThrowInDebugMode(): void
    {
        $handler = new MvcErrorHandler(true);
        $handler->register();

        $this->addToAssertionCount(1);
    }

    public function testRegisterDoesNotThrowInProductionMode(): void
    {
        $handler = new MvcErrorHandler(false);
        $handler->register();

        $this->addToAssertionCount(1);
    }

    public function testMultipleRegisterCallsDoNotThrow(): void
    {
        $handler = new MvcErrorHandler(true);
        $handler->register();
        $handler->register();

        $this->addToAssertionCount(1);
    }
}
