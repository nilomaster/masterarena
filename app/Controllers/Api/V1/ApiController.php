<?php
// Master Arena SaaS - API v1 Index and Health Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use Database\Connection;
use Throwable;

class ApiController extends BaseController
{
    // API root discovery and documentation summary
    public function index(Request $request): void
    {
        $appConfig = require __DIR__ . '/../../../../config/app.php';

        $data = [
            'name' => $appConfig['name'] ?? 'MASTER ARENA',
            'company' => $appConfig['company'] ?? 'MasterDev Solutions',
            'version' => '1.0.0',
            'api_version' => 'v1',
            'status' => 'operational',
            'endpoints' => [
                'health' => '/api/v1/health',
                'ping' => '/api/v1/ping',
                'version' => '/api/v1/version',
                'auth' => [
                    'login' => 'POST /api/v1/auth/login',
                    'logout' => 'POST /api/v1/auth/logout',
                    'refresh' => 'POST /api/v1/auth/refresh',
                ],
                'resources' => [
                    'arenas' => '/api/v1/arenas',
                    'modalidades' => '/api/v1/modalidades',
                    'quadras' => '/api/v1/quadras',
                    'horarios' => '/api/v1/horarios',
                    'valores' => '/api/v1/valores',
                    'clientes' => '/api/v1/clientes',
                    'agendamentos' => '/api/v1/agendamentos',
                    'bloqueios' => '/api/v1/bloqueios',
                    'cupons' => '/api/v1/cupons',
                    'promocoes' => '/api/v1/promocoes',
                    'dashboard' => '/api/v1/dashboard',
                    'relatorios' => '/api/v1/relatorios',
                ]
            ],
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
        ];

        $this->success($data, 'MASTER ARENA API REST v1 operacional.');
    }

    // Health check endpoint
    public function health(Request $request): void
    {
        $dbStatus = 'disconnected';
        $dbError = null;

        try {
            $pdo = Connection::getInstance();
            $stmt = $pdo->query("SELECT 1");
            if ($stmt->fetchColumn() == 1) {
                $dbStatus = 'connected';
            }
        } catch (Throwable $e) {
            $dbStatus = 'error';
            $dbError = $e->getMessage();
        }

        $isHealthy = ($dbStatus === 'connected');

        $data = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'database' => [
                'status' => $dbStatus,
                'error' => $dbError,
            ],
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
        ];

        $statusCode = $isHealthy ? 200 : 503;
        $message = $isHealthy ? 'Servico saudavel e operacional.' : 'Servico com degradacao na conexao com o banco.';

        $this->success($data, $message, $statusCode);
    }

    // Simple ping/pong endpoint for connectivity checks
    public function ping(Request $request): void
    {
        $this->success(['pong' => true, 'time' => microtime(true)], 'pong');
    }

    // Version details endpoint
    public function version(Request $request): void
    {
        $data = [
            'api_version' => 'v1.0.0',
            'architecture' => 'RESTful JSON',
            'supported_clients' => [
                'Web Public / Admin' => 'JavaScript Fetch API',
                'Desktop App' => 'C# .NET Windows Forms',
            ],
            'timezone' => 'America/Sao_Paulo',
        ];

        $this->success($data, 'Informacoes de versao da API.');
    }
}
