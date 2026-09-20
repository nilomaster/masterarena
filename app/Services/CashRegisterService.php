<?php
// Master Arena SaaS - CashRegisterService (Cash Management & Shifts)
// Comments strictly in ASCII only.

namespace App\Services;

use App\Models\CaixaMovimentacao;
use App\Models\CaixaSessao;
use Database\Connection;
use PDO;
use Throwable;

class CashRegisterService
{
    private PDO $pdo;
    private CaixaSessao $caixaSessaoModel;
    private CaixaMovimentacao $movimentacaoModel;
    private AuditLogger $auditLogger;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->caixaSessaoModel = new CaixaSessao($this->pdo);
        $this->movimentacaoModel = new CaixaMovimentacao($this->pdo);
        $this->auditLogger = new AuditLogger($this->pdo);
    }

    // Get current cash register status and live balance
    public function getShiftStatus(int $arenaId): array
    {
        $openSession = $this->caixaSessaoModel->findOpenSession($arenaId);
        if (!$openSession) {
            return [
                'status' => 'FECHADO',
                'caixa_aberto' => false,
                'sessao' => null,
                'balanco' => null,
            ];
        }

        $sessionId = (int)$openSession['id'];
        $balance = $this->caixaSessaoModel->calculateCurrentBalance($sessionId);

        return [
            'status' => 'ABERTO',
            'caixa_aberto' => true,
            'sessao' => $openSession,
            'balanco' => $balance,
        ];
    }

    // Open a new cash register shift
    public function openShift(int $arenaId, int $userId, float $saldoInicial, ?string $observacoes = null): array
    {
        // Check if there is already an open session
        $existing = $this->caixaSessaoModel->findOpenSession($arenaId);
        if ($existing) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'Ja existe um turno de caixa aberto para esta arena (Turno #' . $existing['id'] . '). Feche-o antes de abrir um novo.',
            ];
        }

        $sessionId = $this->caixaSessaoModel->open($arenaId, $userId, $saldoInicial, $observacoes);
        $session = $this->caixaSessaoModel->findById($sessionId);

        $this->auditLogger->log(
            $arenaId,
            $userId,
            'OPEN_CASH_REGISTER',
            'caixa_sessoes',
            $sessionId,
            ['saldo_inicial' => $saldoInicial, 'observacoes' => $observacoes]
        );

        return [
            'success' => true,
            'code' => 201,
            'message' => 'Turno de caixa aberto com sucesso.',
            'sessao' => $session,
        ];
    }

    // Close an open shift and compute physical vs system variance
    public function closeShift(int $sessionId, int $arenaId, int $userId, float $saldoInformado, ?string $observacoes = null): array
    {
        $session = $this->caixaSessaoModel->findById($sessionId);
        if (!$session || (int)$session['arena_id'] !== $arenaId) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Turno de caixa nao encontrado para esta arena.',
            ];
        }

        if ($session['status'] === 'FECHADO') {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Este turno de caixa ja foi encerrado anteriormente.',
            ];
        }

        $balance = $this->caixaSessaoModel->calculateCurrentBalance($sessionId);
        $saldoSistema = $balance['saldo_dinheiro_esperado'];
        $diferenca = round($saldoInformado - $saldoSistema, 2);

        $success = $this->caixaSessaoModel->close(
            $sessionId,
            $userId,
            $saldoInformado,
            $saldoSistema,
            $diferenca,
            $observacoes
        );

        if (!$success) {
            return [
                'success' => false,
                'code' => 500,
                'message' => 'Falha ao encerrar turno de caixa.',
            ];
        }

        $this->auditLogger->log(
            $arenaId,
            $userId,
            'CLOSE_CASH_REGISTER',
            'caixa_sessoes',
            $sessionId,
            [
                'saldo_informado' => $saldoInformado,
                'saldo_sistema' => $saldoSistema,
                'diferenca' => $diferenca,
            ]
        );

        $closedSession = $this->caixaSessaoModel->findById($sessionId);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Turno de caixa encerrado com sucesso.',
            'sessao' => $closedSession,
            'balanco' => $balance,
            'diferenca' => $diferenca,
            'status_diferenca' => ($diferenca == 0.0) ? 'EXATO' : (($diferenca > 0) ? 'SOBRA' : 'FALTA'),
        ];
    }

    // Register a petty cash movement (Suprimento / Sangria)
    public function createMovement(
        int $arenaId,
        int $userId,
        string $tipo,
        float $valor,
        string $motivo
    ): array {
        $openSession = $this->caixaSessaoModel->findOpenSession($arenaId);
        if (!$openSession) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Nao ha nenhum turno de caixa aberto nesta arena para registrar movimentacoes.',
            ];
        }

        $sessionId = (int)$openSession['id'];
        $tipo = strtoupper(trim($tipo));

        if (!in_array($tipo, ['SUPRIMENTO', 'SANGRIA', 'DESPESA'], true)) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Tipo de movimentacao invalido. Valores aceitos: SUPRIMENTO, SANGRIA ou DESPESA.',
            ];
        }

        if ($valor <= 0) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Valor da movimentacao deve ser superior a zero.',
            ];
        }

        // In case of Sangria or Despesa, verify if drawer has enough cash
        if (in_array($tipo, ['SANGRIA', 'DESPESA'], true)) {
            $currentBalance = $this->caixaSessaoModel->calculateCurrentBalance($sessionId);
            if ($valor > $currentBalance['saldo_dinheiro_esperado']) {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => 'Saldo insuficiente em dinheiro no caixa para esta retirada. Saldo atual: R$ ' . number_format($currentBalance['saldo_dinheiro_esperado'], 2, ',', '.'),
                ];
            }
        }

        $movId = $this->movimentacaoModel->create(
            $arenaId,
            $sessionId,
            $tipo,
            $valor,
            $motivo,
            $userId
        );

        $mov = $this->movimentacaoModel->findById($movId);

        $this->auditLogger->log(
            $arenaId,
            $userId,
            'CASH_REGISTER_MOVEMENT',
            'caixa_movimentacoes',
            $movId,
            ['tipo' => $tipo, 'valor' => $valor, 'motivo' => $motivo, 'sessao_id' => $sessionId]
        );

        return [
            'success' => true,
            'code' => 201,
            'message' => 'Movimentacao de caixa registrada com sucesso.',
            'movimentacao' => $mov,
            'balanco_atualizado' => $this->caixaSessaoModel->calculateCurrentBalance($sessionId),
        ];
    }
}
