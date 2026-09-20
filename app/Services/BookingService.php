<?php
// Master Arena SaaS - Booking & Conflict Prevention Core Engine
// Comments strictly in ASCII only.

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Bloqueio;
use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Quadra;
use App\Models\ValorHorario;
use Database\Connection;
use DateTime;
use PDO;
use Throwable;

class BookingService
{
    private PDO $pdo;
    private Agendamento $agendamentoModel;
    private Cliente $clienteModel;
    private Quadra $quadraModel;
    private Bloqueio $bloqueioModel;
    private Cupom $cupomModel;
    private ValorHorario $valorHorarioModel;
    private AuditLogger $auditLogger;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->agendamentoModel = new Agendamento($this->pdo);
        $this->clienteModel = new Cliente($this->pdo);
        $this->quadraModel = new Quadra($this->pdo);
        $this->bloqueioModel = new Bloqueio($this->pdo);
        $this->cupomModel = new Cupom($this->pdo);
        $this->valorHorarioModel = new ValorHorario($this->pdo);
        $this->auditLogger = new AuditLogger($this->pdo);
    }

    // Atomic booking creation with row locking to eliminate double bookings
    public function createBooking(int $arenaId, array $data, ?int $userId = null): array
    {
        $quadraId = (int)($data['quadra_id'] ?? 0);
        $date = trim((string)($data['data'] ?? ''));
        $horaInicio = substr(trim((string)($data['hora_inicio'] ?? '')), 0, 8);
        $horaFim = substr(trim((string)($data['hora_fim'] ?? '')), 0, 8);

        // Normalize time formats (ensure HH:MM:00)
        if (strlen($horaInicio) === 5) $horaInicio .= ':00';
        if (strlen($horaFim) === 5) $horaFim .= ':00';

        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Data em formato invalido. Utilize o padrao YYYY-MM-DD.',
            ];
        }

        $dayOfWeek = (int)$dt->format('w'); // 0=Sun, 1=Mon, ..., 6=Sat

        // Verify court existence and active status
        $court = $this->quadraModel->findById($quadraId);
        if (!$court || (int)$court['arena_id'] !== $arenaId) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Quadra nao encontrada nesta arena.',
            ];
        }

        if ($court['status'] !== 'ATIVO') {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Esta quadra nao esta disponivel para agendamentos no momento (Status: ' . $court['status'] . ').',
            ];
        }

        // Check if overlaps any maintenance or administrative blocks
        $blockConflict = $this->bloqueioModel->isTimeBlocked($arenaId, $quadraId, $date, $horaInicio, $horaFim);
        if ($blockConflict) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'Horario bloqueado para manutencao ou evento: ' . $blockConflict['motivo'],
            ];
        }

        // Resolve official hourly price from pricing engine
        $defaultPrice = (float)$court['valor_padrao'];
        $modalidadeId = (int)$court['modalidade_id'];

        $valorOriginal = $this->valorHorarioModel->resolvePrice(
            $arenaId,
            $quadraId,
            $modalidadeId,
            $dayOfWeek,
            $horaInicio,
            $horaFim,
            $defaultPrice
        );

        $this->pdo->beginTransaction();

        try {
            // Row-level lock: verify no overlapping active bookings exist for this court/time
            $sqlConflict = "SELECT `id`, `status` FROM `agendamentos` 
                            WHERE `arena_id` = :arena_id 
                              AND `quadra_id` = :quadra_id 
                              AND `data` = :data 
                              AND `hora_inicio` < :hora_fim 
                              AND `hora_fim` > :hora_inicio 
                              AND `status` IN ('PENDENTE', 'CONFIRMADO') 
                            FOR UPDATE";

            $stmtLock = $this->pdo->prepare($sqlConflict);
            $stmtLock->execute([
                ':arena_id' => $arenaId,
                ':quadra_id' => $quadraId,
                ':data' => $date,
                ':hora_inicio' => $horaInicio,
                ':hora_fim' => $horaFim,
            ]);

            $existingBooking = $stmtLock->fetch();
            if ($existingBooking) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'code' => 409,
                    'message' => 'Conflito de horario: esta quadra ja possui uma reserva no periodo solicitado.',
                ];
            }

            // Identify or auto-create customer record
            $clienteId = null;
            if (!empty($data['cliente_id'])) {
                $c = $this->clienteModel->findById((int)$data['cliente_id']);
                if ($c && (int)$c['arena_id'] === $arenaId) {
                    $clienteId = (int)$c['id'];
                }
            }

            if (!$clienteId) {
                $clientePayload = $data['cliente'] ?? [
                    'nome' => $data['cliente_nome'] ?? $data['nome_cliente'] ?? $data['nome'] ?? 'Cliente Avulso',
                    'whatsapp' => $data['cliente_telefone'] ?? $data['whatsapp'] ?? $data['telefone'] ?? '11999999999',
                    'cpf' => $data['cliente_cpf'] ?? $data['cpf'] ?? null,
                    'email' => $data['cliente_email'] ?? $data['email'] ?? null,
                ];
                $clienteId = $this->clienteModel->findOrCreateByContact($arenaId, $clientePayload);
            }


            // Voucher / Coupon validation and discount computation
            $desconto = 0.00;
            $valorFinal = $valorOriginal;
            $cupomId = null;

            if (!empty($data['cupom_codigo'])) {
                $cupomResult = $this->cupomModel->validateCupom(
                    $arenaId,
                    (string)$data['cupom_codigo'],
                    $clienteId,
                    $valorOriginal
                );

                if (!$cupomResult['valid']) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'code' => 422,
                        'message' => $cupomResult['message'],
                    ];
                }

                $desconto = (float)$cupomResult['desconto'];
                $valorFinal = (float)$cupomResult['valor_final'];
                $cupomId = (int)$cupomResult['cupom']['id'];
            }

            // Initial status: CONFIRMADO for managers or when explicitly specified, else PENDENTE
            $initialStatus = !empty($data['status']) ? strtoupper(trim($data['status'])) : ($userId ? 'CONFIRMADO' : 'PENDENTE');
            if (!in_array($initialStatus, ['PENDENTE', 'CONFIRMADO'], true)) {
                $initialStatus = 'PENDENTE';
            }

            // Insert booking record
            $stmtInsert = $this->pdo->prepare("INSERT INTO `agendamentos` (
                `arena_id`, `quadra_id`, `cliente_id`, `data`, `hora_inicio`, `hora_fim`,
                `valor_original`, `desconto`, `valor_final`, `cupom_id`, `status`, `observacao`, `criado_por`
            ) VALUES (
                :arena_id, :quadra_id, :cliente_id, :data, :hora_inicio, :hora_fim,
                :valor_original, :desconto, :valor_final, :cupom_id, :status, :observacao, :criado_por
            )");

            $observacao = isset($data['observacao']) ? trim((string)$data['observacao']) : null;

            $stmtInsert->execute([
                ':arena_id' => $arenaId,
                ':quadra_id' => $quadraId,
                ':cliente_id' => $clienteId,
                ':data' => $date,
                ':hora_inicio' => $horaInicio,
                ':hora_fim' => $horaFim,
                ':valor_original' => $valorOriginal,
                ':desconto' => $desconto,
                ':valor_final' => $valorFinal,
                ':cupom_id' => $cupomId,
                ':status' => $initialStatus,
                ':observacao' => $observacao,
                ':criado_por' => $userId,
            ]);

            $bookingId = (int)$this->pdo->lastInsertId();

            // Record voucher usage if coupon was applied
            if ($cupomId) {
                $this->cupomModel->recordUsage($cupomId, $arenaId, $clienteId, $bookingId, $desconto);
            }

            // Record initial history transition
            $this->agendamentoModel->recordHistory(
                $bookingId,
                $arenaId,
                $userId,
                'CRIACAO',
                null,
                $initialStatus,
                'Reserva criada com sucesso'
            );

            $this->pdo->commit();

            // Register audit log
            $this->auditLogger->log(
                $arenaId,
                $userId,
                'CREATE_AGENDAMENTO',
                'agendamentos',
                $bookingId,
                [
                    'quadra_id' => $quadraId,
                    'data' => $date,
                    'hora_inicio' => $horaInicio,
                    'hora_fim' => $horaFim,
                    'valor_final' => $valorFinal,
                    'status' => $initialStatus,
                ]
            );

            $newBooking = $this->agendamentoModel->findById($bookingId);

            return [
                'success' => true,
                'code' => 201,
                'message' => 'Reserva criada com sucesso.',
                'booking' => $newBooking,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Erro interno ao processar a reserva: ' . $e->getMessage(),
            ];
        }
    }

    // Cancel a booking and record cancellation reason in history
    public function cancelBooking(int $bookingId, int $arenaId, ?int $userId = null, string $motivo = 'Cancelamento'): array
    {
        $booking = $this->agendamentoModel->findById($bookingId);
        if (!$booking || (int)$booking['arena_id'] !== $arenaId) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Reserva nao encontrada nesta arena.',
            ];
        }

        if ($booking['status'] === 'CANCELADO') {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Esta reserva ja se encontra cancelada.',
            ];
        }

        $success = $this->agendamentoModel->changeStatus($bookingId, 'CANCELADO', $userId, $motivo);

        if (!$success) {
            return [
                'success' => false,
                'code' => 500,
                'message' => 'Falha ao cancelar reserva.',
            ];
        }

        $this->auditLogger->log(
            $arenaId,
            $userId,
            'CANCEL_AGENDAMENTO',
            'agendamentos',
            $bookingId,
            ['motivo' => $motivo]
        );

        $updated = $this->agendamentoModel->findById($bookingId);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Reserva cancelada com sucesso.',
            'booking' => $updated,
        ];
    }
}
