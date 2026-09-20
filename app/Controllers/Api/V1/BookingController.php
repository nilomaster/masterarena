<?php
// Master Arena SaaS - Booking & Reservation API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Agendamento;
use App\Models\Arena;
use App\Models\Cliente;
use App\Services\AuditLogger;
use App\Services\BookingService;

class BookingController extends BaseController
{
    private Agendamento $agendamentoModel;
    private Arena $arenaModel;
    private Cliente $clienteModel;
    private BookingService $bookingService;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->agendamentoModel = new Agendamento();
        $this->arenaModel = new Arena();
        $this->clienteModel = new Cliente();
        $this->bookingService = new BookingService();
        $this->auditLogger = new AuditLogger();
    }

    // List bookings for an arena with multiple filters
    public function index(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser) {
            $this->unauthorized('Autenticacao necessaria para listar agendamentos.');
            return;
        }

        // Multi-tenant check
        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar agendamentos desta arena.');
            return;
        }

        $filters = [];
        if ($request->query('quadra_id')) {
            $filters['quadra_id'] = (int)$request->query('quadra_id');
        }
        if ($request->query('cliente_id')) {
            $filters['cliente_id'] = (int)$request->query('cliente_id');
        }
        if ($request->query('data')) {
            $filters['data'] = trim((string)$request->query('data'));
        }
        if ($request->query('status')) {
            $filters['status'] = strtoupper(trim((string)$request->query('status')));
        }
        if ($request->query('data_inicio')) {
            $filters['data_inicio'] = trim((string)$request->query('data_inicio'));
        }
        if ($request->query('data_fim')) {
            $filters['data_fim'] = trim((string)$request->query('data_fim'));
        }

        // If current user is CLIENTE, restrict filter to their own bookings
        $perfil = strtoupper($currentUser['perfil'] ?? '');
        if ($perfil === 'CLIENTE') {
            $userPhone = $currentUser['telefone'] ?? null;
            if ($userPhone) {
                $client = $this->clienteModel->findByTelefone($arenaId, $userPhone);
                if ($client) {
                    $filters['cliente_id'] = (int)$client['id'];
                } else {
                    $this->success(['total' => 0, 'agendamentos' => []]);
                    return;
                }
            }
        }

        $page = max(1, (int)($request->query('page') ?? 1));
        $perPage = min(100, max(1, (int)($request->query('per_page') ?? 30)));
        $offset = ($page - 1) * $perPage;

        $bookings = $this->agendamentoModel->listByArena($arenaId, $filters, $perPage, $offset);
        $total = $this->agendamentoModel->countByArena($arenaId, $filters);

        $this->success([
            'arena_id' => $arenaId,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'agendamentos' => $bookings,
        ]);
    }

    // Show booking details including client and status history
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $booking = $this->agendamentoModel->findById($id);

        if (!$booking) {
            $this->notFound('Agendamento nao encontrado.');
            return;
        }

        $arenaId = (int)$booking['arena_id'];
        $currentUser = $request->getUser();

        if ($currentUser && !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para visualizar este agendamento.');
            return;
        }

        $history = $this->agendamentoModel->getHistory($id);

        $this->success([
            'agendamento' => $booking,
            'historico' => $history,
        ]);
    }

    // Create a new booking (public customer or authenticated admin/staff)
    public function create(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        $userId = $currentUser ? (int)$currentUser['id'] : null;

        $body = $request->getBody();

        $result = $this->bookingService->createBooking($arenaId, $body, $userId);

        if (!$result['success']) {
            $code = $result['code'] ?? 400;
            $msg = $result['message'] ?? 'Falha ao realizar reserva.';

            if ($code === 409) {
                $this->conflict($msg);
            } elseif ($code === 404) {
                $this->notFound($msg);
            } elseif ($code === 422) {
                $this->unprocessableEntity([], $msg);
            } else {
                $this->badRequest($msg);
            }
            return;
        }

        $this->created([
            'agendamento' => $result['booking'],
        ], $result['message']);
    }

    // Update booking status (Admin / Staff)
    public function updateStatus(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $booking = $this->agendamentoModel->findById($id);

        if (!$booking) {
            $this->notFound('Agendamento nao encontrado.');
            return;
        }

        $arenaId = (int)$booking['arena_id'];
        $currentUser = $request->getUser();

        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para alterar status desta reserva.');
            return;
        }

        $body = $request->getBody();
        $newStatus = strtoupper(trim((string)($body['status'] ?? '')));
        $motivo = trim((string)($body['motivo'] ?? 'Alteracao de status pelo operador'));

        $allowedStatuses = ['PENDENTE', 'CONFIRMADO', 'CONCLUIDO', 'CANCELADO', 'NAO_COMPARECEU'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            $this->badRequest('Status invalido. Valores aceitos: ' . implode(', ', $allowedStatuses));
            return;
        }

        $userId = (int)$currentUser['id'];

        if ($newStatus === 'CANCELADO') {
            $cancelResult = $this->bookingService->cancelBooking($id, $arenaId, $userId, $motivo);
            if (!$cancelResult['success']) {
                $this->badRequest($cancelResult['message']);
                return;
            }
            $this->success(['agendamento' => $cancelResult['booking']], $cancelResult['message']);
            return;
        }

        $success = $this->agendamentoModel->changeStatus($id, $newStatus, $userId, $motivo);
        if (!$success) {
            $this->badRequest('Nao foi possivel atualizar o status da reserva.');
            return;
        }

        $this->auditLogger->log(
            $arenaId,
            $userId,
            'UPDATE_STATUS_AGENDAMENTO',
            'agendamentos',
            $id,
            ['status_anterior' => $booking['status'], 'novo_status' => $newStatus, 'motivo' => $motivo],
            $request->getIp(),
            $request->getUserAgent()
        );

        $updated = $this->agendamentoModel->findById($id);

        $this->success(['agendamento' => $updated], 'Status atualizado com sucesso.');
    }

    // Cancel booking (Admin or Customer)
    public function cancel(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $booking = $this->agendamentoModel->findById($id);

        if (!$booking) {
            $this->notFound('Agendamento nao encontrado.');
            return;
        }

        $arenaId = (int)$booking['arena_id'];
        $currentUser = $request->getUser();

        // Check permissions: Admin/Staff of this arena OR client itself
        if ($currentUser) {
            $perfil = strtoupper($currentUser['perfil'] ?? '');
            if ($perfil !== 'CLIENTE' && !$this->canAccessArena($currentUser, $arenaId)) {
                $this->forbidden('Acesso negado para cancelar esta reserva.');
                return;
            }
        }

        $body = $request->getBody();
        $motivo = trim((string)($body['motivo'] ?? 'Solicitacao de cancelamento'));
        $userId = $currentUser ? (int)$currentUser['id'] : null;

        $cancelResult = $this->bookingService->cancelBooking($id, $arenaId, $userId, $motivo);

        if (!$cancelResult['success']) {
            $code = $cancelResult['code'] ?? 400;
            if ($code === 404) {
                $this->notFound($cancelResult['message']);
            } else {
                $this->badRequest($cancelResult['message']);
            }
            return;
        }

        $this->success(['agendamento' => $cancelResult['booking']], $cancelResult['message']);
    }

    // Get today stats for dashboard metrics
    public function stats(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar metricas desta arena.');
            return;
        }

        $todayCount = $this->agendamentoModel->countToday($arenaId);
        $totalBookings = $this->agendamentoModel->countByArena($arenaId);

        $this->success([
            'arena_id' => $arenaId,
            'reservas_hoje' => $todayCount,
            'total_reservas' => $totalBookings,
        ]);
    }

    // Helper to verify multi-tenant access rights
    private function canAccessArena(?array $user, int $targetArenaId): bool
    {
        if (!$user) {
            return false;
        }

        $perfil = strtoupper($user['perfil'] ?? '');
        if ($perfil === 'SUPERADMIN') {
            return true;
        }

        $userArenaId = isset($user['arena_id']) ? (int)$user['arena_id'] : null;
        return $userArenaId === $targetArenaId;
    }
}
