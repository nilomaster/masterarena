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

// Root route
$router->get('/', function (Request $req) {
    $dbStatus = 'disconnected';
    try {
        $pdo = Connection::getInstance();
        $stmt = $pdo->query("SELECT 1");
        if ($stmt->fetchColumn() == 1) {
            $dbStatus = 'connected';
        }
    } catch (Throwable $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    Response::success([
        'app' => 'MASTER ARENA SaaS',
        'status' => 'operational',
        'database' => $dbStatus,
        'api_documentation' => '/api/v1',
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get(),
    ], 'MASTER ARENA SaaS - Sistema Operacional.');
});

// Load API routes
require_once __DIR__ . '/../routes/api.php';

// Dispatch incoming request
$router->dispatch($request);
