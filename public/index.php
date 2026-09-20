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

// Dashboard route: Serve administrative web dashboard
$router->get('/dashboard', function (Request $req) {
    $dashboardPath = __DIR__ . '/../resources/views/dashboard.php';
    if (file_exists($dashboardPath)) {
        require_once $dashboardPath;
        exit;
    }
    Response::notFound('Dashboard view not found.');
});

// Customer Web Portal routes: /arena/{slug} or /portal/{slug}
$portalHandler = function (Request $req) {
    $portalPath = __DIR__ . '/../resources/views/portal.php';
    if (file_exists($portalPath)) {
        require_once $portalPath;
        exit;
    }
    Response::notFound('Portal view not found.');
};
$router->get('/arena/{slug}', $portalHandler);
$router->get('/portal/{slug}', $portalHandler);

// Self-service Kiosk (Totem Touchscreen) route: /totem/{slug}
$router->get('/totem/{slug}', function (Request $req) {
    $totemPath = __DIR__ . '/../resources/views/totem.php';
    if (file_exists($totemPath)) {
        require_once $totemPath;
        exit;
    }
    Response::notFound('Totem view not found.');
});

// User setup and password reset tool routes
$userSetupHandler = function (Request $req) {
    require_once __DIR__ . '/../database/create_users.php';
    exit;
};
$router->get('/database/create_users.php', $userSetupHandler);
$router->post('/database/create_users.php', $userSetupHandler);
$router->get('/setup/users', $userSetupHandler);
$router->post('/setup/users', $userSetupHandler);

// Database web installer and seeder routes
$dbSetupHandler = function (Request $req) {
    require_once __DIR__ . '/../database/web_setup.php';
    exit;
};
$router->get('/database/web_setup.php', $dbSetupHandler);
$router->post('/database/web_setup.php', $dbSetupHandler);
$router->get('/setup/database', $dbSetupHandler);
$router->post('/setup/database', $dbSetupHandler);

// Load API routes if file exists
$apiRoutesFile = __DIR__ . '/../routes/api.php';
if (file_exists($apiRoutesFile)) {
    require_once $apiRoutesFile;
}

// Dispatch incoming request
$router->dispatch($request);
