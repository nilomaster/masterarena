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
use App\Controllers\Api\V1\ModalidadeController;
use App\Controllers\Api\V1\QuadraController;
use App\Controllers\Api\V1\ScheduleController;
use App\Controllers\Api\V1\CupomController;
use App\Controllers\Api\V1\BookingController;
use App\Controllers\Api\V1\PaymentController;
use App\Controllers\Api\V1\CashRegisterController;
use App\Controllers\Api\V1\PortalController;
use App\Middleware\OptionalAuthMiddleware;

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

    // Sports (Modalidades) Endpoints
    $api->get('/arenas/{arena_id}/modalidades', [ModalidadeController::class, 'index'], [OptionalAuthMiddleware::class]);
    $api->post('/arenas/{arena_id}/modalidades', [ModalidadeController::class, 'create'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->get('/modalidades/{id}', [ModalidadeController::class, 'show'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->put('/modalidades/{id}', [ModalidadeController::class, 'update'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->delete('/modalidades/{id}', [ModalidadeController::class, 'delete'], [AuthMiddleware::class, RequireAdminMiddleware::class]);

    // Courts (Quadras) Endpoints
    $api->get('/arenas/{arena_id}/quadras', [QuadraController::class, 'index'], [OptionalAuthMiddleware::class]);
    $api->post('/arenas/{arena_id}/quadras', [QuadraController::class, 'create'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->get('/quadras/{id}', [QuadraController::class, 'show'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->put('/quadras/{id}', [QuadraController::class, 'update'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->patch('/quadras/{id}/status', [QuadraController::class, 'updateStatus'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->delete('/quadras/{id}', [QuadraController::class, 'delete'], [AuthMiddleware::class, RequireAdminMiddleware::class]);

    // Schedule & Availability Grid Engine Endpoints
    $api->get('/arenas/{arena_id}/grade', [ScheduleController::class, 'grade'], [OptionalAuthMiddleware::class]);
    $api->get('/arenas/{arena_id}/horarios', [ScheduleController::class, 'getHorarios'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/arenas/{arena_id}/horarios', [ScheduleController::class, 'saveHorarios'], [AuthMiddleware::class, RequireAdminMiddleware::class]);

    // Court Blocks (Bloqueios)
    $api->get('/arenas/{arena_id}/bloqueios', [ScheduleController::class, 'listBloqueios'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/arenas/{arena_id}/bloqueios', [ScheduleController::class, 'createBloqueio'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->delete('/bloqueios/{id}', [ScheduleController::class, 'deleteBloqueio'], [AuthMiddleware::class, RequireAdminMiddleware::class]);

    // Dynamic Pricing Rules (Valores Horarios)
    $api->get('/arenas/{arena_id}/valores-horarios', [ScheduleController::class, 'listValores'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/arenas/{arena_id}/valores-horarios', [ScheduleController::class, 'createValor'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->delete('/valores-horarios/{id}', [ScheduleController::class, 'deleteValor'], [AuthMiddleware::class, RequireAdminMiddleware::class]);

    // Discount Coupons (Cupons) Endpoints
    $api->get('/arenas/{arena_id}/cupons', [CupomController::class, 'index'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/arenas/{arena_id}/cupons', [CupomController::class, 'create'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->get('/cupons/{id}', [CupomController::class, 'show'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->put('/cupons/{id}', [CupomController::class, 'update'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->patch('/cupons/{id}/status', [CupomController::class, 'updateStatus'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->delete('/cupons/{id}', [CupomController::class, 'delete'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/arenas/{arena_id}/cupons/validar', [CupomController::class, 'validar'], [OptionalAuthMiddleware::class]);


    // Bookings & Reservations Endpoints
    $api->get('/arenas/{arena_id}/agendamentos', [BookingController::class, 'index'], [AuthMiddleware::class]);
    $api->post('/arenas/{arena_id}/agendamentos', [BookingController::class, 'create'], [OptionalAuthMiddleware::class]);
    $api->get('/arenas/{arena_id}/agendamentos/stats', [BookingController::class, 'stats'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->get('/agendamentos/{id}', [BookingController::class, 'show'], [OptionalAuthMiddleware::class]);
    $api->patch('/agendamentos/{id}/status', [BookingController::class, 'updateStatus'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/agendamentos/{id}/cancelar', [BookingController::class, 'cancel'], [OptionalAuthMiddleware::class]);

    // Financial, Payments & PIX Endpoints
    $api->post('/agendamentos/{id}/pix', [PaymentController::class, 'createBookingPix'], [OptionalAuthMiddleware::class]);
    $api->get('/arenas/{arena_id}/pagamentos', [PaymentController::class, 'index'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->post('/arenas/{arena_id}/pagamentos', [PaymentController::class, 'createDirect'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->get('/pagamentos/{id}', [PaymentController::class, 'show'], [AuthMiddleware::class]);
    $api->post('/pagamentos/{id}/confirmar', [PaymentController::class, 'confirm'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->post('/pagamentos/{id}/estornar', [PaymentController::class, 'refund'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->get('/arenas/{arena_id}/financeiro/resumo', [PaymentController::class, 'summary'], [AuthMiddleware::class, RequireAdminMiddleware::class]);
    $api->post('/webhooks/pix', [PaymentController::class, 'webhookPix']);

    // Cash Register (Caixa Balcao) Endpoints
    $api->get('/arenas/{arena_id}/caixa/status', [CashRegisterController::class, 'status'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->post('/arenas/{arena_id}/caixa/abrir', [CashRegisterController::class, 'open'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->post('/arenas/{arena_id}/caixa/movimentacao', [CashRegisterController::class, 'movement'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->post('/arenas/{arena_id}/caixa/fechar', [CashRegisterController::class, 'close'], [AuthMiddleware::class, RequireStaffMiddleware::class]);
    $api->get('/arenas/{arena_id}/caixa/historico', [CashRegisterController::class, 'history'], [AuthMiddleware::class, RequireAdminMiddleware::class]);

    // Customer Web Portal & Totem Public Endpoints
    $api->get('/arenas/{arena_id}/portal-info', [PortalController::class, 'getPortalInfo']);
    $api->get('/arenas/slug/{slug}/portal-info', [PortalController::class, 'getPortalInfo']);
    $api->post('/arenas/{arena_id}/clientes/minhas-reservas', [PortalController::class, 'getMyBookings']);
    $api->get('/agendamentos/{id}/public-status', [PortalController::class, 'getPublicStatus']);
    $api->post('/arenas/{arena_id}/checkin', [PortalController::class, 'checkin']);
}, [
    CorsMiddleware::class,
    JsonMiddleware::class,
]);


