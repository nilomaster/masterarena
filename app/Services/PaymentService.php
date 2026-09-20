<?php
// Master Arena SaaS - Payment & Transaction Service
// Comments strictly in ASCII only.

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Arena;
use App\Models\CaixaSessao;
use App\Models\Cliente;
use App\Models\Pagamento;
use Database\Connection;
use PDO;
use Throwable;

class PaymentService
{
    private PDO $pdo;
    private Pagamento $pagamentoModel;
    private Agendamento $agendamentoModel;
    private CaixaSessao $caixaSessaoModel;
    private Arena $arenaModel;
    private PixPayloadService $pixPayloadService;
    private AuditLogger $auditLogger;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->pagamentoModel = new Pagamento($this->pdo);
        $this->agendamentoModel = new Agendamento($this->pdo);
        $this->caixaSessaoModel = new CaixaSessao($this->pdo);
        $this->arenaModel = new Arena($this->pdo);
        $this->pixPayloadService = new PixPayloadService();
        $this->auditLogger = new AuditLogger($this->pdo);
    }

    // Generate dynamic PIX payment for a specific booking
    public function createBookingPix(int $agendamentoId, int $arenaId, ?int $userId = null): array
    {
        $booking = $this->agendamentoModel->findById($agendamentoId);
        if (!$booking || (int)$booking['arena_id'] !== $arenaId) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Agendamento nao encontrado para esta arena.',
            ];
        }

        if ($booking['status'] === 'CANCELADO') {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Nao e possivel gerar cobranca para uma reserva cancelada.',
            ];
        }

        // Check if an active payment already exists for this booking
        $existing = $this->pagamentoModel->findByAgendamentoId($agendamentoId);
        if ($existing && $existing['status'] === 'PAGO') {
            return [
                'success' => true,
                'code' => 200,
                'message' => 'Esta reserva ja se encontra paga.',
                'payment' => $existing,
            ];
        }

        $arena = $this->arenaModel->findById($arenaId);
        $arenaName = $arena['nome_arena'] ?? 'MASTER ARENA';

        // Check arena settings override if exists
        $settings = $this->arenaModel->getSettings($arenaId);
        $pixKey = !empty($settings['chave_pix']) ? (string)$settings['chave_pix'] : ($arena['cnpj'] ?? $arena['telefone'] ?? 'pix@masterarena.com.br');


        $amount = (float)$booking['valor_final'];
        $txid = 'AG' . $agendamentoId . 'T' . time();

        $pixPayload = $this->pixPayloadService->generatePayload(
            $pixKey,
            $amount,
            $txid,
            $arenaName,
            'SAO PAULO',
            "Reserva Arena #{$agendamentoId}"
        );

        $dataExp = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // If existing pending payment, update its payload
        if ($existing && $existing['status'] === 'PENDENTE') {
            $paymentId = (int)$existing['id'];
            $stmtUp = $this->pdo->prepare("UPDATE `pagamentos` SET 
                `pix_copia_cola` = :payload,
                `data_expiracao` = :data_exp,
                `valor` = :valor,
                `valor_liquido` = :valor
                WHERE `id` = :id");
            $stmtUp->execute([
                ':payload' => $pixPayload,
                ':data_exp' => $dataExp,
                ':valor' => $amount,
                ':id' => $paymentId,
            ]);
        } else {
            $paymentId = $this->pagamentoModel->create($arenaId, [
                'agendamento_id' => $agendamentoId,
                'cliente_id' => $booking['cliente_id'] ?? null,
                'tipo' => 'RECEITA',
                'categoria' => 'RESERVA_QUADRA',
                'descricao' => "Reserva de Quadra #{$agendamentoId} ({$booking['quadra_nome']})",
                'metodo_pagamento' => 'PIX',
                'valor' => $amount,
                'valor_taxa' => 0.00,
                'valor_liquido' => $amount,
                'status' => 'PENDENTE',
                'gateway' => 'INTERNO',
                'gateway_transaction_id' => $txid,
                'pix_copia_cola' => $pixPayload,
                'data_expiracao' => $dataExp,
                'criado_por' => $userId,
            ]);
        }

        $payment = $this->pagamentoModel->findById($paymentId);

        return [
            'success' => true,
            'code' => 201,
            'message' => 'Cobranca PIX gerada com sucesso.',
            'payment' => $payment,
            'pix' => [
                'copia_e_cola' => $pixPayload,
                'expira_em' => $dataExp,
                'valor' => $amount,
                'txid' => $txid,
            ],
        ];
    }

    // Confirm settlement of a payment (manual counter settlement or automated webhook callback)
    public function confirmPayment(int $paymentId, int $arenaId, ?int $userId = null, ?string $metodo = null): array
    {
        $payment = $this->pagamentoModel->findById($paymentId);
        if (!$payment || (int)$payment['arena_id'] !== $arenaId) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Pagamento nao encontrado para esta arena.',
            ];
        }

        if ($payment['status'] === 'PAGO') {
            return [
                'success' => true,
                'code' => 200,
                'message' => 'Este pagamento ja foi confirmado anteriormente.',
                'payment' => $payment,
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $now = date('Y-m-d H:i:s');
            $newMethod = $metodo ? strtoupper(trim($metodo)) : $payment['metodo_pagamento'];

            // Attach to current open cash register if available
            $openSession = $this->caixaSessaoModel->findOpenSession($arenaId);
            $sessionId = $openSession ? (int)$openSession['id'] : null;

            $stmtUp = $this->pdo->prepare("UPDATE `pagamentos` SET 
                `status` = 'PAGO',
                `metodo_pagamento` = :metodo,
                `data_pagamento` = :data_pag,
                `caixa_sessao_id` = COALESCE(:caixa_sessao_id, `caixa_sessao_id`)
                WHERE `id` = :id");

            $stmtUp->execute([
                ':metodo' => $newMethod,
                ':data_pag' => $now,
                ':caixa_sessao_id' => $sessionId,
                ':id' => $paymentId,
            ]);

            // If payment is linked to a booking, automatically confirm the booking
            $agendamentoId = !empty($payment['agendamento_id']) ? (int)$payment['agendamento_id'] : null;
            if ($agendamentoId) {
                $this->agendamentoModel->changeStatus(
                    $agendamentoId,
                    'CONFIRMADO',
                    $userId,
                    "Pagamento #{$paymentId} confirmado via {$newMethod}"
                );
            }

            // Audit log
            $this->auditLogger->log(
                $arenaId,
                $userId,
                'CONFIRM_PAYMENT',
                'pagamentos',
                $paymentId,
                [
                    'valor' => $payment['valor'],
                    'metodo' => $newMethod,
                    'agendamento_id' => $agendamentoId,
                ]
            );

            $this->pdo->commit();

            $updatedPayment = $this->pagamentoModel->findById($paymentId);

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Pagamento confirmado com sucesso.',
                'payment' => $updatedPayment,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Erro ao processar confirmacao de pagamento: ' . $e->getMessage(),
            ];
        }
    }

    // Create a direct counter payment or expense entry
    public function createDirectPayment(int $arenaId, array $data, ?int $userId = null): array
    {
        $valor = (float)($data['valor'] ?? 0);
        if ($valor <= 0) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Valor da transacao deve ser maior que zero.',
            ];
        }

        $tipo = strtoupper(trim((string)($data['tipo'] ?? 'RECEITA')));
        $metodo = strtoupper(trim((string)($data['metodo_pagamento'] ?? 'DINHEIRO')));
        $status = strtoupper(trim((string)($data['status'] ?? 'PAGO')));

        // Check if there is an open cash register session for cash movements
        $openSession = $this->caixaSessaoModel->findOpenSession($arenaId);
        $sessionId = $openSession ? (int)$openSession['id'] : null;

        $paymentData = [
            'agendamento_id' => !empty($data['agendamento_id']) ? (int)$data['agendamento_id'] : null,
            'cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            'caixa_sessao_id' => $sessionId,
            'tipo' => in_array($tipo, ['RECEITA', 'DESPESA'], true) ? $tipo : 'RECEITA',
            'categoria' => strtoupper(trim((string)($data['categoria'] ?? 'BALCAO'))),
            'descricao' => isset($data['descricao']) ? trim((string)$data['descricao']) : null,
            'metodo_pagamento' => $metodo,
            'valor' => $valor,
            'valor_taxa' => (float)($data['valor_taxa'] ?? 0.00),
            'valor_liquido' => $valor - (float)($data['valor_taxa'] ?? 0.00),
            'status' => $status,
            'gateway' => 'INTERNO',
            'data_pagamento' => ($status === 'PAGO') ? date('Y-m-d H:i:s') : null,
            'criado_por' => $userId,
        ];

        try {
            $this->pdo->beginTransaction();

            $paymentId = $this->pagamentoModel->create($arenaId, $paymentData);

            // If linked to booking and paid, update booking
            if (!empty($paymentData['agendamento_id']) && $status === 'PAGO') {
                $this->agendamentoModel->changeStatus(
                    (int)$paymentData['agendamento_id'],
                    'CONFIRMADO',
                    $userId,
                    "Recebimento de balcao registrado (#{$paymentId}) via {$metodo}"
                );
            }

            $this->auditLogger->log(
                $arenaId,
                $userId,
                'CREATE_PAYMENT',
                'pagamentos',
                $paymentId,
                $paymentData
            );

            $this->pdo->commit();

            $created = $this->pagamentoModel->findById($paymentId);

            return [
                'success' => true,
                'code' => 201,
                'message' => 'Transacao registrada com sucesso.',
                'payment' => $created,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Erro ao salvar pagamento: ' . $e->getMessage(),
            ];
        }
    }

    // Refund / Reversal of a settled payment
    public function refundPayment(int $paymentId, int $arenaId, ?int $userId = null, string $motivo = 'Estorno'): array
    {
        $payment = $this->pagamentoModel->findById($paymentId);
        if (!$payment || (int)$payment['arena_id'] !== $arenaId) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Pagamento nao encontrado para esta arena.',
            ];
        }

        if ($payment['status'] === 'ESTORNADO') {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Este pagamento ja se encontra estornado.',
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $this->pagamentoModel->updateStatus($paymentId, 'ESTORNADO');

            // Audit
            $this->auditLogger->log(
                $arenaId,
                $userId,
                'REFUND_PAYMENT',
                'pagamentos',
                $paymentId,
                ['motivo' => $motivo, 'valor' => $payment['valor']]
            );

            $this->pdo->commit();

            $updated = $this->pagamentoModel->findById($paymentId);

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Pagamento estornado com sucesso.',
                'payment' => $updated,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Erro ao estornar pagamento: ' . $e->getMessage(),
            ];
        }
    }
}
