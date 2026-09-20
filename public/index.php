<?php
// Master Arena SaaS - Public Web Entry Point & REST API Dispatcher
// Comments strictly in ASCII only.

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use Database\Connection;

// Register PSR-4 Autoloader
Autoloader::register();

// Global Exception Handler
set_exception_handler(function (Throwable $e) {
    Response::serverError(
        'Erro interno no processamento do servidor.',
        $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
    );
});

// Global PHP Error Handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    Response::serverError(
        'Erro interno de execucao PHP.',
        "{$message} in {$file}:{$line}"
    );
});

$request = new Request();
$router = new Router();

// Root route: Serve rich web landing page for browser, or clean JSON status if explicitly requested
$router->get('/', function (Request $req) {
    if (!$req->wantsJson() && !defined('MASTER_ARENA_TEST_MODE')) {
        $landingPath = __DIR__ . '/../resources/views/landing.php';
        if (file_exists($landingPath)) {
            require_once $landingPath;
            exit;
        }
    }

    Response::success([
        'app' => 'MASTER ARENA SaaS',
        'status' => 'operational',
        'version' => '1.0.0',
    ], 'MASTER ARENA SaaS - Sistema Operacional.');
});

// Load API routes if file exists
$apiRoutesFile = __DIR__ . '/../routes/api.php';
if (file_exists($apiRoutesFile)) {
    require_once $apiRoutesFile;
}

// Dispatch incoming request
$router->dispatch($request);
