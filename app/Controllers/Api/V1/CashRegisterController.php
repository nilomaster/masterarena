<?php
// Master Arena SaaS - Cash Register Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\CaixaSessao;
use App\Services\CashRegisterService;

class CashRegisterController extends BaseController
{
    private Arena $arenaModel;
    private CaixaSessao $caixaSessaoModel;
    private CashRegisterService $cashRegisterService;

    public function __construct()
    {
        $this->arenaModel = new Arena();
        $this->caixaSessaoModel = new CaixaSessao();
        $this->cashRegisterService = new CashRegisterService();
    }

    // Get current cash register status and balance
    public function status(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar caixa desta arena.');
            return;
        }

        $status = $this->cashRegisterService->getShiftStatus($arenaId);

        $this->success(['caixa' => $status]);
    }

    // Open a new cash register shift
    public function open(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para abrir caixa desta arena.');
            return;
        }

        $body = $request->getBody();
        $saldoInicial = (float)($body['saldo_inicial'] ?? 0.00);
        $observacoes = isset($body['observacoes']) ? (string)$body['observacoes'] : null;
        $userId = (int)$currentUser['id'];

        $result = $this->cashRegisterService->openShift($arenaId, $userId, $saldoInicial, $observacoes);

        if (!$result['success']) {
            $code = $result['code'] ?? 400;
            if ($code === 409) $this->conflict($result['message']);
            else $this->badRequest($result['message']);
            return;
        }

        $this->created(['sessao' => $result['sessao']], $result['message']);
    }

    // Register a cash movement (Sangria / Suprimento / Despesa)
    public function movement(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para movimentar caixa desta arena.');
            return;
        }

        $body = $request->getBody();
        $tipo = strtoupper(trim((string)($body['tipo'] ?? '')));
        $valor = (float)($body['valor'] ?? 0);
        $motivo = trim((string)($body['motivo'] ?? ''));

        if (empty($motivo)) {
            $this->badRequest('Motivo da movimentacao e obrigatorio.');
            return;
        }

        $userId = (int)$currentUser['id'];

        $result = $this->cashRegisterService->createMovement($arenaId, $userId, $tipo, $valor, $motivo);

        if (!$result['success']) {
            $code = $result['code'] ?? 400;
            if ($code === 422) $this->unprocessableEntity([], $result['message']);
            else $this->badRequest($result['message']);
            return;
        }

        $this->created([
            'movimentacao' => $result['movimentacao'],
            'balanco' => $result['balanco_atualizado'],
        ], $result['message']);
    }

    // Close an active cash register shift
    public function close(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para fechar caixa desta arena.');
            return;
        }

        $body = $request->getBody();
        $sessionId = (int)($body['caixa_sessao_id'] ?? 0);

        if ($sessionId <= 0) {
            $open = $this->caixaSessaoModel->findOpenSession($arenaId);
            if ($open) {
                $sessionId = (int)$open['id'];
            }
        }

        if ($sessionId <= 0) {
            $this->badRequest('Nenhum turno de caixa aberto localizado para encerramento.');
            return;
        }

        $saldoInformado = (float)($body['saldo_informado'] ?? 0.00);
        $observacoes = isset($body['observacoes']) ? (string)$body['observacoes'] : null;
        $userId = (int)$currentUser['id'];

        $result = $this->cashRegisterService->closeShift($sessionId, $arenaId, $userId, $saldoInformado, $observacoes);

        if (!$result['success']) {
            $this->badRequest($result['message']);
            return;
        }

        $this->success([
            'sessao' => $result['sessao'],
            'balanco' => $result['balanco'],
            'diferenca' => $result['diferenca'],
            'status_diferenca' => $result['status_diferenca'],
        ], $result['message']);
    }

    // List shifts history for an arena
    public function history(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para consultar historico de caixa desta arena.');
            return;
        }

        $page = max(1, (int)($request->query('page') ?? 1));
        $perPage = min(100, max(1, (int)($request->query('per_page') ?? 30)));
        $offset = ($page - 1) * $perPage;

        $history = $this->caixaSessaoModel->listByArena($arenaId, $perPage, $offset);

        $this->success([
            'arena_id' => $arenaId,
            'page' => $page,
            'per_page' => $perPage,
            'sessoes' => $history,
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
