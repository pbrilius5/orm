<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Processor\ProcessorInterface;

/**
 * Custom processor to add application structure information to log records
 */
class AppStructProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        // Add application environment info
        $record['extra']['app_env'] = $_SERVER['APP_ENV'] ?? 'unknown';
        $record['extra']['app_debug'] = (bool) ($_SERVER['APP_DEBUG'] ?? false);

        // Add PHP version
        $record['extra']['php_version'] = phpversion();

        // Add script name
        $record['extra']['script_name'] = $_SERVER['SCRIPT_NAME'] ?? 'unknown';

        return $record;
    }
}
