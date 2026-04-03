<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;

class CrashLogger implements LoggerInterface
{
    private const LOG_DIR = 'var/log';
    private const LOG_FILE = self::LOG_DIR . '/crash.log';

    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('crash');
        $this->logger->pushHandler(new StreamHandler(self::LOG_FILE, Level::Critical));
        $this->logger->pushProcessor(new UidProcessor());
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public static function isCrash(\Throwable $e): bool
    {
        return (
            $e instanceof \Error
            || $e instanceof \OutOfMemoryError
            || stripos($e->getMessage(), 'Segmentation fault') !== false
            || stripos($e->getMessage(), 'Killed') !== false
        );
    }
}
