<?php
// Master Arena SaaS - API Routes Registry
// Comments strictly in ASCII only.

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;
use App\Middleware\RequireAdminMiddleware;
use App\Middleware\RequireStaffMiddleware;
use App\Middleware\RequireSuperadminMiddleware;
use App\Controllers\Api\V1\ApiController;
use App\Controllers\Api\V1\AuthController;
use App\Controllers\Api\V1\ArenaController;

/** @var Router $router */

$router->group('/api/v1', function (Router $api) {
    // API discovery and status routes
    $api->get('/', [ApiController::class, 'index']);
    $api->get('/health', [ApiController::class, 'health']);
    $api->get('/ping', [ApiController::class, 'ping']);
    $api->get('/version', [ApiController::class, 'version']);

    // Public Authentication routes
    $api->post('/auth/login', [AuthController::class, 'login']);
    $api->post('/auth/refresh', [AuthController::class, 'refresh']);

    // Authenticated Profile and Session routes
    $api->get('/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);
    $api->post('/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

    // Role-Based Access Control demonstration endpoints
    $api->get('/superadmin/only', function (Request $req) {
        Response::success(['access' => 'granted', 'role' => 'SUPERADMIN'], 'Acesso autorizado para Superadmin.');
    }, [AuthMiddleware::class, RequireSuperadminMiddleware::class]);

    $api->get('/admin/only', function (Request $req) {
        Response::success(['access' => 'granted', 'roles' => ['SUPERADMIN', 'ADMIN']], 'Acesso autorizado para Administrador.');
    }, [AuthMiddleware::class, RequireAdminMiddleware::class]);

    $api->get('/staff/access', function (Request $req) {
        Response::success(['access' => 'granted', 'roles' => ['SUPERADMIN', 'ADMIN', 'FUNCIONARIO']], 'Acesso autorizado para Equipe da Arena.');
    }, [AuthMiddleware::class, RequireStaffMiddleware::class]);

    // Public Arena Lookup by Slug (Client Booking Portal)
    $api->get('/arenas/by-slug/{slug}', [ArenaController::class, 'bySlug']);

    // Multi-Tenant Arena Management Endpoints
    $api->get('/arenas', [ArenaController::class, 'index'], [AuthMiddleware::class, RequireSuperadminMiddleware::class]);
    $api->post('/arenas', [ArenaController::class, 'create'], [AuthMiddleware::class, RequireSuperadminMiddleware::class]);
    $api->get('/arenas/{id}', [ArenaController::class, 'show'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->put('/arenas/{id}', [ArenaController::class, 'update'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->patch('/arenas/{id}/status', [ArenaController::class, 'updateStatus'], [AuthMiddleware::class, RequireSuperadminMiddleware::class]);
    $api->get('/arenas/{id}/settings', [ArenaController::class, 'getSettings'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->put('/arenas/{id}/settings', [ArenaController::class, 'updateSettings'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
}, [
    CorsMiddleware::class,
    JsonMiddleware::class,
]);
