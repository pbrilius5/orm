<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Custom processor to add application structure information to log records
 */
class AppStructProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record = $record->with(extra: array_merge($record->extra, [
            'app_env' => $_SERVER['APP_ENV'] ?? 'unknown',
            'app_debug' => (bool) ($_SERVER['APP_DEBUG'] ?? false),
            'php_version' => phpversion(),
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        ]));

        return $record;
    }
}
