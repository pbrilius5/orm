<?php

declare(strict_types=1);

/**
 * MVC Entry Point - Vanilla PHP, no laminas/diactoros.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\ErrorHandler\MvcErrorHandler;
use App\MvcApplication;
use Oryx\ORM\EntityManagerFactory;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$errorHandler = new MvcErrorHandler($_SERVER['APP_DEBUG'] ?? true);
$errorHandler->register();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$em = EntityManagerFactory::getInstance();
$app = new MvcApplication($em);
$app->run();
