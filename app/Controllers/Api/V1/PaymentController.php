<?php
// Master Arena SaaS - Payment & Financial Transactions Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Agendamento;
use App\Models\Arena;
use App\Models\Pagamento;
use App\Services\PaymentService;

class PaymentController extends BaseController
{
    private Pagamento $pagamentoModel;
    private Agendamento $agendamentoModel;
    private Arena $arenaModel;
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->pagamentoModel = new Pagamento();
        $this->agendamentoModel = new Agendamento();
        $this->arenaModel = new Arena();
        $this->paymentService = new PaymentService();
    }

    // List payments for an arena
    public function index(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar transacoes desta arena.');
            return;
        }

        $filters = [];
        if ($request->query('status')) $filters['status'] = (string)$request->query('status');
        if ($request->query('tipo')) $filters['tipo'] = (string)$request->query('tipo');
        if ($request->query('metodo_pagamento')) $filters['metodo_pagamento'] = (string)$request->query('metodo_pagamento');
        if ($request->query('cliente_id')) $filters['cliente_id'] = (int)$request->query('cliente_id');
        if ($request->query('data_inicio')) $filters['data_inicio'] = (string)$request->query('data_inicio');
        if ($request->query('data_fim')) $filters['data_fim'] = (string)$request->query('data_fim');

        $page = max(1, (int)($request->query('page') ?? 1));
        $perPage = min(100, max(1, (int)($request->query('per_page') ?? 30)));
        $offset = ($page - 1) * $perPage;

        $payments = $this->pagamentoModel->listByArena($arenaId, $filters, $perPage, $offset);
        $total = $this->pagamentoModel->countByArena($arenaId, $filters);

        $this->success([
            'arena_id' => $arenaId,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pagamentos' => $payments,
        ]);
    }

    // Get single payment details
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $payment = $this->pagamentoModel->findById($id);

        if (!$payment) {
            $this->notFound('Pagamento nao encontrado.');
            return;
        }

        $arenaId = (int)$payment['arena_id'];
        $currentUser = $request->getUser();

        if ($currentUser && !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para visualizar este pagamento.');
            return;
        }

        $this->success(['pagamento' => $payment]);
    }

    // Generate PIX QR Code for an existing booking
    public function createBookingPix(Request $request): void
    {
        $agendamentoId = (int)$request->getParam('id');
        $booking = $this->agendamentoModel->findById($agendamentoId);

        if (!$booking) {
            $this->notFound('Agendamento nao encontrado.');
            return;
        }

        $arenaId = (int)$booking['arena_id'];
        $currentUser = $request->getUser();
        $userId = $currentUser ? (int)$currentUser['id'] : null;

        $result = $this->paymentService->createBookingPix($agendamentoId, $arenaId, $userId);

        if (!$result['success']) {
            $code = $result['code'] ?? 400;
            if ($code === 404) $this->notFound($result['message']);
            else $this->badRequest($result['message']);
            return;
        }

        $this->created([
            'pagamento' => $result['payment'],
            'pix' => $result['pix'] ?? null,
        ], $result['message']);
    }

    // Register a direct counter payment or expense entry
    public function createDirect(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para registrar pagamentos nesta arena.');
            return;
        }

        $body = $request->getBody();
        $userId = (int)$currentUser['id'];

        $result = $this->paymentService->createDirectPayment($arenaId, $body, $userId);

        if (!$result['success']) {
            $this->badRequest($result['message']);
            return;
        }

        $this->created(['pagamento' => $result['payment']], $result['message']);
    }

    // Confirm settlement of a payment (manual or system)
    public function confirm(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $payment = $this->pagamentoModel->findById($id);

        if (!$payment) {
            $this->notFound('Pagamento nao encontrado.');
            return;
        }

        $arenaId = (int)$payment['arena_id'];
        $currentUser = $request->getUser();

        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para confirmar este pagamento.');
            return;
        }

        $body = $request->getBody();
        $metodo = isset($body['metodo_pagamento']) ? (string)$body['metodo_pagamento'] : null;
        $userId = (int)$currentUser['id'];

        $result = $this->paymentService->confirmPayment($id, $arenaId, $userId, $metodo);

        if (!$result['success']) {
            $this->badRequest($result['message']);
            return;
        }

        $this->success(['pagamento' => $result['payment']], $result['message']);
    }

    // Refund / Reverse a payment
    public function refund(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $payment = $this->pagamentoModel->findById($id);

        if (!$payment) {
            $this->notFound('Pagamento nao encontrado.');
            return;
        }

        $arenaId = (int)$payment['arena_id'];
        $currentUser = $request->getUser();

        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para estornar este pagamento.');
            return;
        }

        $body = $request->getBody();
        $motivo = trim((string)($body['motivo'] ?? 'Solicitacao de estorno'));
        $userId = (int)$currentUser['id'];

        $result = $this->paymentService->refundPayment($id, $arenaId, $userId, $motivo);

        if (!$result['success']) {
            $this->badRequest($result['message']);
            return;
        }

        $this->success(['pagamento' => $result['payment']], $result['message']);
    }

    // Webhook receiver for PIX instant callback notification
    public function webhookPix(Request $request): void
    {
        $body = $request->getBody();
        $paymentId = (int)($body['payment_id'] ?? $body['pagamento_id'] ?? 0);
        $txid = trim((string)($body['txid'] ?? ''));

        if ($paymentId <= 0 && empty($txid)) {
            $this->badRequest('Identificador do pagamento ou txid e obrigatorio.');
            return;
        }

        $payment = null;
        if ($paymentId > 0) {
            $payment = $this->pagamentoModel->findById($paymentId);
        }

        if (!$payment && !empty($txid)) {
            // Find by gateway transaction id
            $stmt = \Database\Connection::getInstance()->prepare("SELECT * FROM `pagamentos` WHERE `gateway_transaction_id` = :txid LIMIT 1");
            $stmt->execute([':txid' => $txid]);
            $payment = $stmt->fetch();
        }

        if (!$payment) {
            $this->notFound('Cobranca correspondente ao webhook nao localizada.');
            return;
        }

        $arenaId = (int)$payment['arena_id'];
        $result = $this->paymentService->confirmPayment((int)$payment['id'], $arenaId, null, 'PIX');

        $this->success([
            'notificacao_processada' => true,
            'pagamento_id' => (int)$payment['id'],
            'status' => 'PAGO',
        ], 'Notificacao PIX processada com sucesso.');
    }

    // Financial executive summary metrics
    public function summary(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar resumo financeiro desta arena.');
            return;
        }

        $date = $request->query('data') ? (string)$request->query('data') : date('Y-m-d');
        $summary = $this->pagamentoModel->getExecutiveSummary($arenaId, $date);

        $this->success(['resumo' => $summary]);
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
