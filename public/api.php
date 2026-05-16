<?php

declare(strict_types=1);

/**
 * API Entry Point - Uses guzzlehttp/psr7 for PSR-7/PSR-15.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use GuzzleHttp\Psr7\ServerRequest;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev');
$request = ServerRequest::fromGlobals();
$response = $kernel->handle($request);

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value));
    }
}
http_response_code($response->getStatusCode());
$body = $response->getBody();
if ($body->isSeekable()) {
    $body->rewind();
}
while (!$body->eof()) {
    echo $body->read(8192);
}

$kernel->terminate();
