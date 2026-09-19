<?php
// Master Arena SaaS - API Routes Registry
// Comments strictly in ASCII only.

use App\Core\Router;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;
use App\Controllers\Api\V1\ApiController;

/** @var Router $router */

$router->group('/api/v1', function (Router $api) {
    // API discovery and status routes
    $api->get('/', [ApiController::class, 'index']);
    $api->get('/health', [ApiController::class, 'health']);
    $api->get('/ping', [ApiController::class, 'ping']);
    $api->get('/version', [ApiController::class, 'version']);
}, [
    CorsMiddleware::class,
    JsonMiddleware::class,
]);
