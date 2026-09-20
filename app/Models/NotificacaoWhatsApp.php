<?php
// Master Arena SaaS - WhatsApp Notification Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class NotificacaoWhatsApp
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Create a new WhatsApp notification record
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `notificacoes_whatsapp` (
            `arena_id`, `cliente_id`, `agendamento_id`, `tipo`, `telefone`, 
            `mensagem`, `status`, `gateway_provider`, `gateway_response`, 
            `enviado_em`, `tentativas`, `erro`
        ) VALUES (
            :arena_id, :cliente_id, :agendamento_id, :tipo, :telefone, 
            :mensagem, :status, :gateway_provider, :gateway_response, 
            :enviado_em, :tentativas, :erro
        )");

        $stmt->execute([
            ':arena_id' => (int)$data['arena_id'],
            ':cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            ':agendamento_id' => !empty($data['agendamento_id']) ? (int)$data['agendamento_id'] : null,
            ':tipo' => strtoupper(trim((string)$data['tipo'])),
            ':telefone' => trim((string)$data['telefone']),
            ':mensagem' => trim((string)$data['mensagem']),
            ':status' => strtoupper(trim((string)($data['status'] ?? 'PENDENTE'))),
            ':gateway_provider' => strtoupper(trim((string)($data['gateway_provider'] ?? 'SIMULATOR'))),
            ':gateway_response' => isset($data['gateway_response']) ? (string)$data['gateway_response'] : null,
            ':enviado_em' => !empty($data['enviado_em']) ? $data['enviado_em'] : null,
            ':tentativas' => isset($data['tentativas']) ? (int)$data['tentativas'] : 0,
            ':erro' => isset($data['erro']) ? (string)$data['erro'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Find notification by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT n.*, 
                    c.nome AS cliente_nome, 
                    ag.data AS agendamento_data, 
                    ag.hora_inicio AS agendamento_hora_inicio 
                FROM `notificacoes_whatsapp` n 
                LEFT JOIN `clientes` c ON n.cliente_id = c.id 
                LEFT JOIN `agendamentos` ag ON n.agendamento_id = ag.id 
                WHERE n.`id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Check if reminder was already sent for a booking
    public function hasReminderBeenSent(int $bookingId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `notificacoes_whatsapp` 
            WHERE `agendamento_id` = :booking_id 
              AND `tipo` = 'LEMBRETE_JOGO' 
              AND `status` = 'ENVIADO'");
        $stmt->execute([':booking_id' => $bookingId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    // List notifications for an arena with filters and pagination
    public function listByArena(int $arenaId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT n.*, 
                    c.nome AS cliente_nome, 
                    ag.data AS agendamento_data, 
                    ag.hora_inicio AS agendamento_hora_inicio,
                    q.nome AS quadra_nome 
                FROM `notificacoes_whatsapp` n 
                LEFT JOIN `clientes` c ON n.cliente_id = c.id 
                LEFT JOIN `agendamentos` ag ON n.agendamento_id = ag.id 
                LEFT JOIN `quadras` q ON ag.quadra_id = q.id 
                WHERE n.`arena_id` = :arena_id";

        $params = [':arena_id' => $arenaId];

        if (!empty($filters['tipo'])) {
            $sql .= " AND n.`tipo` = :tipo";
            $params[':tipo'] = strtoupper(trim((string)$filters['tipo']));
        }

        if (!empty($filters['status'])) {
            $sql .= " AND n.`status` = :status";
            $params[':status'] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['telefone'])) {
            $sql .= " AND n.`telefone` LIKE :telefone";
            $params[':telefone'] = '%' . preg_replace('/\D/', '', (string)$filters['telefone']) . '%';
        }

        $sql .= " ORDER BY n.`id` DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    // Count notifications for an arena matching filter criteria
    public function countByArena(int $arenaId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM `notificacoes_whatsapp` n WHERE n.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if (!empty($filters['tipo'])) {
            $sql .= " AND n.`tipo` = :tipo";
            $params[':tipo'] = strtoupper(trim((string)$filters['tipo']));
        }

        if (!empty($filters['status'])) {
            $sql .= " AND n.`status` = :status";
            $params[':status'] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['telefone'])) {
            $sql .= " AND n.`telefone` LIKE :telefone";
            $params[':telefone'] = '%' . preg_replace('/\D/', '', (string)$filters['telefone']) . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // Mark notification as successfully sent
    public function markAsSent(int $id, string $gatewayResponse = ''): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `notificacoes_whatsapp` SET 
            `status` = 'ENVIADO',
            `gateway_response` = :response,
            `enviado_em` = :enviado_em,
            `tentativas` = `tentativas` + 1,
            `erro` = NULL 
            WHERE `id` = :id");

        return $stmt->execute([
            ':id' => $id,
            ':response' => $gatewayResponse,
            ':enviado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    // Mark notification as failed
    public function markAsFailed(int $id, string $error): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `notificacoes_whatsapp` SET 
            `status` = 'FALHA',
            `tentativas` = `tentativas` + 1,
            `erro` = :erro 
            WHERE `id` = :id");

        return $stmt->execute([
            ':id' => $id,
            ':erro' => $error,
        ]);
    }
}
