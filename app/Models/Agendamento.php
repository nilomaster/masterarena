<?php
// Master Arena SaaS - Agendamento (Booking) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Agendamento
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find booking by ID with complete court, sport, customer and voucher details
    public function findById(int $id): ?array
    {
        $sql = "SELECT a.*, 
                    q.nome AS quadra_nome, 
                    m.id AS modalidade_id,
                    m.nome AS modalidade_nome,
                    c.nome AS cliente_nome, 
                    c.whatsapp AS cliente_whatsapp, 
                    c.email AS cliente_email,
                    c.cpf AS cliente_cpf,
                    cp.codigo AS cupom_codigo
                FROM `agendamentos` a
                LEFT JOIN `quadras` q ON a.quadra_id = q.id
                LEFT JOIN `modalidades` m ON q.modalidade_id = m.id
                LEFT JOIN `clientes` c ON a.cliente_id = c.id
                LEFT JOIN `cupons` cp ON a.cupom_id = cp.id
                WHERE a.`id` = :id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // List bookings with extensive multi-tenant filtering and pagination
    public function listByArena(
        int $arenaId,
        array|string|null $filtersOrDate = null,
        ?int $quadraId = null,
        ?string $status = null,
        ?int $clienteId = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $filters = [];
        if (is_array($filtersOrDate)) {
            $filters = $filtersOrDate;
            // When first parameter is array, $quadraId is $limit and $status is $offset if numeric
            if (is_numeric($quadraId)) {
                $limit = (int)$quadraId;
            }
            if (is_numeric($status)) {
                $offset = (int)$status;
            }
        } else {
            $filters['data'] = $filtersOrDate;
            $filters['quadra_id'] = $quadraId;
            $filters['status'] = $status;
            $filters['cliente_id'] = $clienteId;
        }

        $sql = "SELECT a.*, 
                    q.nome AS quadra_nome, 
                    m.nome AS modalidade_nome,
                    c.nome AS cliente_nome, 
                    c.whatsapp AS cliente_whatsapp, 
                    cp.codigo AS cupom_codigo
                FROM `agendamentos` a
                LEFT JOIN `quadras` q ON a.quadra_id = q.id
                LEFT JOIN `modalidades` m ON q.modalidade_id = m.id
                LEFT JOIN `clientes` c ON a.cliente_id = c.id
                LEFT JOIN `cupons` cp ON a.cupom_id = cp.id
                WHERE a.`arena_id` = :arena_id";

        $params = [':arena_id' => $arenaId];

        $targetDate = $filters['data'] ?? $filters['date'] ?? null;
        if (!empty($targetDate)) {
            $sql .= " AND a.`data` = :data";
            $params[':data'] = trim((string)$targetDate);
        }

        if (!empty($filters['quadra_id'])) {
            $sql .= " AND a.`quadra_id` = :quadra_id";
            $params[':quadra_id'] = (int)$filters['quadra_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.`status` = :status";
            $params[':status'] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['cliente_id'])) {
            $sql .= " AND a.`cliente_id` = :cliente_id";
            $params[':cliente_id'] = (int)$filters['cliente_id'];
        }

        if (!empty($filters['data_inicio'])) {
            $sql .= " AND a.`data` >= :data_inicio";
            $params[':data_inicio'] = trim((string)$filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $sql .= " AND a.`data` <= :data_fim";
            $params[':data_fim'] = trim((string)$filters['data_fim']);
        }

        $sql .= " ORDER BY a.`data` DESC, a.`hora_inicio` ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    // Count bookings for an arena matching filter criteria
    public function countByArena(int $arenaId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM `agendamentos` a WHERE a.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        $targetDate = $filters['data'] ?? $filters['date'] ?? null;
        if (!empty($targetDate)) {
            $sql .= " AND a.`data` = :data";
            $params[':data'] = trim((string)$targetDate);
        }

        if (!empty($filters['quadra_id'])) {
            $sql .= " AND a.`quadra_id` = :quadra_id";
            $params[':quadra_id'] = (int)$filters['quadra_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.`status` = :status";
            $params[':status'] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['cliente_id'])) {
            $sql .= " AND a.`cliente_id` = :cliente_id";
            $params[':cliente_id'] = (int)$filters['cliente_id'];
        }

        if (!empty($filters['data_inicio'])) {
            $sql .= " AND a.`data` >= :data_inicio";
            $params[':data_inicio'] = trim((string)$filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $sql .= " AND a.`data` <= :data_fim";
            $params[':data_fim'] = trim((string)$filters['data_fim']);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }


    // Count today's bookings for dashboard live metric
    public function countToday(int $arenaId): int
    {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `agendamentos` 
            WHERE `arena_id` = :arena_id AND `data` = :data AND `status` != 'CANCELADO'");
        $stmt->execute([':arena_id' => $arenaId, ':data' => $today]);

        return (int)$stmt->fetchColumn();
    }

    // Change booking status and automatically register transition history
    public function changeStatus(int $id, string $newStatus, ?int $userId = null, ?string $motivo = null): bool
    {
        $newStatus = strtoupper(trim($newStatus));
        $validStatuses = ['PENDENTE', 'CONFIRMADO', 'CANCELADO', 'CONCLUIDO', 'BLOQUEADO'];

        if (!in_array($newStatus, $validStatuses, true)) {
            return false;
        }

        $current = $this->findById($id);
        if (!$current) {
            return false;
        }

        $oldStatus = $current['status'];
        if ($oldStatus === $newStatus) {
            return true;
        }

        $stmt = $this->pdo->prepare("UPDATE `agendamentos` SET `status` = :status WHERE `id` = :id");
        $success = $stmt->execute([':status' => $newStatus, ':id' => $id]);

        if ($success) {
            $this->recordHistory(
                $id,
                (int)$current['arena_id'],
                $userId,
                'ALTERACAO_STATUS',
                $oldStatus,
                $newStatus,
                $motivo
            );
        }

        return $success;
    }

    // Record immutable audit history entry
    public function recordHistory(
        int $agendamentoId,
        int $arenaId,
        ?int $userId,
        string $acao,
        ?string $statusAnterior,
        string $statusNovo,
        ?string $motivo = null
    ): bool {
        $stmt = $this->pdo->prepare("INSERT INTO `agendamento_historico` (
            `agendamento_id`, `arena_id`, `usuario_id`, `acao`, `status_anterior`, `status_novo`, `motivo`
        ) VALUES (
            :agendamento_id, :arena_id, :usuario_id, :acao, :status_anterior, :status_novo, :motivo
        )");

        return $stmt->execute([
            ':agendamento_id' => $agendamentoId,
            ':arena_id' => $arenaId,
            ':usuario_id' => $userId,
            ':acao' => strtoupper($acao),
            ':status_anterior' => $statusAnterior,
            ':status_novo' => $statusNovo,
            ':motivo' => $motivo,
        ]);
    }

    // Get audit history for a booking
    public function getHistory(int $agendamentoId): array
    {
        $sql = "SELECT h.*, u.nome AS usuario_nome 
                FROM `agendamento_historico` h 
                LEFT JOIN `usuarios` u ON h.usuario_id = u.id 
                WHERE h.`agendamento_id` = :agendamento_id 
                ORDER BY h.`id` ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':agendamento_id' => $agendamentoId]);

        return $stmt->fetchAll() ?: [];
    }
}
