<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use App\Logger\AppStructProcessor;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    private const LOG_DIR = 'var/log';

    public static function create(string $appEnv = 'dev'): LoggerInterface
    {
        $level = match ($appEnv) {
            'dev' => Level::Debug,
            'prod' => Level::Error,
            default => Level::Debug,
        };

        self::ensureLogDirectory();

        $logFile = self::LOG_DIR . '/app_' . $appEnv . '.log';

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logFile, $level));
        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new IntrospectionProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new WebProcessor(
            [], // skipDirs - don't skip any directories
            [], // allowedHeaders - allow all headers (empty array means allow none, but we'll use null for all)
            $_SERVER['REQUEST_URI'] ?? '',
            $_SERVER['REQUEST_METHOD'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ));
        $logger->pushProcessor(new AppStructProcessor());

        return $logger;
    }

    public static function createCrashLogger(): LoggerInterface
    {
        self::ensureLogDirectory();

        $logFile = self::LOG_DIR . '/crash.log';

        $logger = new Logger('crash');
        $logger->pushHandler(new StreamHandler($logFile, Level::Critical));
        $logger->pushProcessor(new UidProcessor());

        return $logger;
    }

    public static function createAsyncFailureLogger(): LoggerInterface
    {
        self::ensureLogDirectory();

        $logFile = self::LOG_DIR . '/async_failures.log';

        $logger = new Logger('async_failures');
        $logger->pushHandler(new StreamHandler($logFile, Level::Error));
        $logger->pushProcessor(new UidProcessor());

        return $logger;
    }

    private static function ensureLogDirectory(): void
    {
        if (!is_dir(self::LOG_DIR)) {
            mkdir(self::LOG_DIR, 0o755, true);
        }
    }
}
