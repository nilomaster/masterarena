<?php
// Master Arena SaaS - Pagamento (Payment & Financial Transaction) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Pagamento
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find payment by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT p.*, 
                    a.nome_arena,
                    c.nome AS cliente_nome, 
                    c.whatsapp AS cliente_whatsapp,
                    ag.data AS agendamento_data, 
                    ag.hora_inicio AS agendamento_hora_inicio, 
                    ag.hora_fim AS agendamento_hora_fim,
                    u.nome AS criado_por_nome
                FROM `pagamentos` p
                LEFT JOIN `arenas` a ON p.arena_id = a.id
                LEFT JOIN `clientes` c ON p.cliente_id = c.id
                LEFT JOIN `agendamentos` ag ON p.agendamento_id = ag.id
                LEFT JOIN `usuarios` u ON p.criado_por = u.id
                WHERE p.`id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Find payment by booking ID
    public function findByAgendamentoId(int $agendamentoId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `pagamentos` WHERE `agendamento_id` = :agendamento_id ORDER BY `id` DESC LIMIT 1");
        $stmt->execute([':agendamento_id' => $agendamentoId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // List payments for an arena with multiple filters and pagination
    public function listByArena(int $arenaId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT p.*, 
                    c.nome AS cliente_nome, 
                    c.whatsapp AS cliente_whatsapp,
                    ag.data AS agendamento_data, 
                    ag.hora_inicio AS agendamento_hora_inicio, 
                    ag.hora_fim AS agendamento_hora_fim,
                    u.nome AS criado_por_nome
                FROM `pagamentos` p
                LEFT JOIN `clientes` c ON p.cliente_id = c.id
                LEFT JOIN `agendamentos` ag ON p.agendamento_id = ag.id
                LEFT JOIN `usuarios` u ON p.criado_por = u.id
                WHERE p.`arena_id` = :arena_id";

        $params = [':arena_id' => $arenaId];

        if (!empty($filters['status'])) {
            $sql .= " AND p.`status` = :status";
            $params[':status'] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND p.`tipo` = :tipo";
            $params[':tipo'] = strtoupper(trim((string)$filters['tipo']));
        }

        if (!empty($filters['metodo_pagamento'])) {
            $sql .= " AND p.`metodo_pagamento` = :metodo_pagamento";
            $params[':metodo_pagamento'] = strtoupper(trim((string)$filters['metodo_pagamento']));
        }

        if (!empty($filters['cliente_id'])) {
            $sql .= " AND p.`cliente_id` = :cliente_id";
            $params[':cliente_id'] = (int)$filters['cliente_id'];
        }

        if (!empty($filters['agendamento_id'])) {
            $sql .= " AND p.`agendamento_id` = :agendamento_id";
            $params[':agendamento_id'] = (int)$filters['agendamento_id'];
        }

        if (!empty($filters['caixa_sessao_id'])) {
            $sql .= " AND p.`caixa_sessao_id` = :caixa_sessao_id";
            $params[':caixa_sessao_id'] = (int)$filters['caixa_sessao_id'];
        }

        if (!empty($filters['data_inicio'])) {
            $sql .= " AND DATE(p.`created_at`) >= :data_inicio";
            $params[':data_inicio'] = trim((string)$filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $sql .= " AND DATE(p.`created_at`) <= :data_fim";
            $params[':data_fim'] = trim((string)$filters['data_fim']);
        }

        $sql .= " ORDER BY p.`id` DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    // Count payments for an arena matching filter criteria
    public function countByArena(int $arenaId, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM `pagamentos` p WHERE p.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if (!empty($filters['status'])) {
            $sql .= " AND p.`status` = :status";
            $params[':status'] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND p.`tipo` = :tipo";
            $params[':tipo'] = strtoupper(trim((string)$filters['tipo']));
        }

        if (!empty($filters['metodo_pagamento'])) {
            $sql .= " AND p.`metodo_pagamento` = :metodo_pagamento";
            $params[':metodo_pagamento'] = strtoupper(trim((string)$filters['metodo_pagamento']));
        }

        if (!empty($filters['cliente_id'])) {
            $sql .= " AND p.`cliente_id` = :cliente_id";
            $params[':cliente_id'] = (int)$filters['cliente_id'];
        }

        if (!empty($filters['data_inicio'])) {
            $sql .= " AND DATE(p.`created_at`) >= :data_inicio";
            $params[':data_inicio'] = trim((string)$filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $sql .= " AND DATE(p.`created_at`) <= :data_fim";
            $params[':data_fim'] = trim((string)$filters['data_fim']);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // Create a new payment record
    public function create(int $arenaId, array $data): int
    {
        $valor = (float)($data['valor'] ?? 0);
        $valorTaxa = (float)($data['valor_taxa'] ?? 0.00);
        $valorLiquido = isset($data['valor_liquido']) ? (float)$data['valor_liquido'] : max(0.00, round($valor - $valorTaxa, 2));

        $stmt = $this->pdo->prepare("INSERT INTO `pagamentos` (
            `arena_id`, `agendamento_id`, `cliente_id`, `caixa_sessao_id`, `tipo`, `categoria`,
            `descricao`, `metodo_pagamento`, `valor`, `valor_taxa`, `valor_liquido`,
            `status`, `gateway`, `gateway_transaction_id`, `pix_copia_cola`,
            `data_pagamento`, `data_expiracao`, `comprovante_url`, `criado_por`
        ) VALUES (
            :arena_id, :agendamento_id, :cliente_id, :caixa_sessao_id, :tipo, :categoria,
            :descricao, :metodo_pagamento, :valor, :valor_taxa, :valor_liquido,
            :status, :gateway, :gateway_transaction_id, :pix_copia_cola,
            :data_pagamento, :data_expiracao, :comprovante_url, :criado_por
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':agendamento_id' => !empty($data['agendamento_id']) ? (int)$data['agendamento_id'] : null,
            ':cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            ':caixa_sessao_id' => !empty($data['caixa_sessao_id']) ? (int)$data['caixa_sessao_id'] : null,
            ':tipo' => strtoupper(trim((string)($data['tipo'] ?? 'RECEITA'))),
            ':categoria' => strtoupper(trim((string)($data['categoria'] ?? 'RESERVA_QUADRA'))),
            ':descricao' => isset($data['descricao']) ? trim((string)$data['descricao']) : null,
            ':metodo_pagamento' => strtoupper(trim((string)($data['metodo_pagamento'] ?? 'PIX'))),
            ':valor' => $valor,
            ':valor_taxa' => $valorTaxa,
            ':valor_liquido' => $valorLiquido,
            ':status' => strtoupper(trim((string)($data['status'] ?? 'PENDENTE'))),
            ':gateway' => trim((string)($data['gateway'] ?? 'INTERNO')),
            ':gateway_transaction_id' => isset($data['gateway_transaction_id']) ? trim((string)$data['gateway_transaction_id']) : null,
            ':pix_copia_cola' => isset($data['pix_copia_cola']) ? trim((string)$data['pix_copia_cola']) : null,
            ':data_pagamento' => isset($data['data_pagamento']) ? trim((string)$data['data_pagamento']) : null,
            ':data_expiracao' => isset($data['data_expiracao']) ? trim((string)$data['data_expiracao']) : null,
            ':comprovante_url' => isset($data['comprovante_url']) ? trim((string)$data['comprovante_url']) : null,
            ':criado_por' => !empty($data['criado_por']) ? (int)$data['criado_por'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Update payment status (e.g. PAGO, CANCELADO, ESTORNADO)
    public function updateStatus(int $id, string $status, ?string $dataPagamento = null): bool
    {
        $status = strtoupper(trim($status));
        $fields = ["`status` = :status"];
        $params = [':id' => $id, ':status' => $status];

        if ($status === 'PAGO') {
            $fields[] = "`data_pagamento` = :data_pagamento";
            $params[':data_pagamento'] = $dataPagamento ?? date('Y-m-d H:i:s');
        }

        $sql = "UPDATE `pagamentos` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Attach payment to an open cash register session
    public function attachCaixaSessao(int $paymentId, int $caixaSessaoId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `pagamentos` SET `caixa_sessao_id` = :caixa_sessao_id WHERE `id` = :id");
        return $stmt->execute([':id' => $paymentId, ':caixa_sessao_id' => $caixaSessaoId]);
    }

    // Financial executive summary metrics for an arena
    public function getExecutiveSummary(int $arenaId, ?string $date = null): array
    {
        $targetDate = $date ?? date('Y-m-d');

        // Total received today
        $stmtReceived = $this->pdo->prepare("SELECT 
                COALESCE(SUM(valor), 0.00) AS total_recebido,
                COALESCE(SUM(valor_liquido), 0.00) AS total_liquido,
                COUNT(*) AS qtd_recebida
            FROM `pagamentos` 
            WHERE `arena_id` = :arena_id 
              AND `status` = 'PAGO' 
              AND `tipo` = 'RECEITA' 
              AND DATE(`data_pagamento`) = :data");
        $stmtReceived->execute([':arena_id' => $arenaId, ':data' => $targetDate]);
        $received = $stmtReceived->fetch() ?: ['total_recebido' => 0.00, 'total_liquido' => 0.00, 'qtd_recebida' => 0];

        // Total pending (a receber) for today's bookings
        $stmtPending = $this->pdo->prepare("SELECT 
                COALESCE(SUM(p.valor), 0.00) AS total_pendente,
                COUNT(*) AS qtd_pendente
            FROM `pagamentos` p
            LEFT JOIN `agendamentos` a ON p.agendamento_id = a.id
            WHERE p.`arena_id` = :arena_id 
              AND p.`status` = 'PENDENTE'
              AND (DATE(p.`created_at`) = :data OR a.`data` = :data_ag)");
        $stmtPending->execute([':arena_id' => $arenaId, ':data' => $targetDate, ':data_ag' => $targetDate]);
        $pending = $stmtPending->fetch() ?: ['total_pendente' => 0.00, 'qtd_pendente' => 0];

        // Expenses today
        $stmtExpenses = $this->pdo->prepare("SELECT 
                COALESCE(SUM(valor), 0.00) AS total_despesas,
                COUNT(*) AS qtd_despesas
            FROM `pagamentos` 
            WHERE `arena_id` = :arena_id 
              AND `status` = 'PAGO' 
              AND `tipo` = 'DESPESA' 
              AND DATE(`data_pagamento`) = :data");
        $stmtExpenses->execute([':arena_id' => $arenaId, ':data' => $targetDate]);
        $expenses = $stmtExpenses->fetch() ?: ['total_despesas' => 0.00, 'qtd_despesas' => 0];

        // By payment method for today's settled transactions
        $stmtMethods = $this->pdo->prepare("SELECT 
                `metodo_pagamento`,
                COALESCE(SUM(valor), 0.00) AS total,
                COUNT(*) AS qtd
            FROM `pagamentos` 
            WHERE `arena_id` = :arena_id 
              AND `status` = 'PAGO' 
              AND `tipo` = 'RECEITA' 
              AND DATE(`data_pagamento`) = :data
            GROUP BY `metodo_pagamento`");
        $stmtMethods->execute([':arena_id' => $arenaId, ':data' => $targetDate]);
        $byMethods = $stmtMethods->fetchAll() ?: [];

        $methodsMap = [
            'PIX' => 0.00,
            'CARTAO_CREDITO' => 0.00,
            'CARTAO_DEBITO' => 0.00,
            'DINHEIRO' => 0.00,
            'TRANSFERENCIA' => 0.00,
        ];
        foreach ($byMethods as $m) {
            $methodsMap[$m['metodo_pagamento']] = (float)$m['total'];
        }

        $faturamentoBruto = (float)$received['total_recebido'];
        $despesasTotal = (float)$expenses['total_despesas'];
        $saldoOperacional = round($faturamentoBruto - $despesasTotal, 2);

        return [
            'data' => $targetDate,
            'faturamento_bruto' => $faturamentoBruto,
            'total_liquido' => (float)$received['total_liquido'],
            'total_pendente' => (float)$pending['total_pendente'],
            'total_despesas' => $despesasTotal,
            'saldo_operacional' => $saldoOperacional,
            'quantidade_transacoes_pagas' => (int)$received['qtd_recebida'],
            'metodos' => $methodsMap,
        ];
    }
}
