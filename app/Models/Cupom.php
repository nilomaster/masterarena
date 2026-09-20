<?php
// Master Arena SaaS - Cupom (Discount Voucher) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Cupom
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find coupon by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `cupons` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Find coupon by code within an arena
    public function findByCode(int $arenaId, string $codigo): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `cupons` WHERE `arena_id` = :arena_id AND `codigo` = :codigo LIMIT 1");
        $stmt->execute([
            ':arena_id' => $arenaId,
            ':codigo' => strtoupper(trim($codigo)),
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // List coupons for an arena
    public function listByArena(int $arenaId, ?bool $onlyActive = null): array
    {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM `cupom_utilizacoes` u WHERE u.`cupom_id` = c.`id`) AS total_utilizacoes 
                FROM `cupons` c 
                WHERE c.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($onlyActive !== null) {
            $sql .= " AND c.`ativo` = :ativo";
            $params[':ativo'] = $onlyActive ? 1 : 0;
        }

        $sql .= " ORDER BY c.`id` DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    // Create a new coupon
    public function create(int $arenaId, array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `cupons` (
            `arena_id`, `codigo`, `descricao`, `tipo`, `valor`, `data_inicio`, `data_fim`, `limite_uso`, `limite_por_cliente`, `ativo`
        ) VALUES (
            :arena_id, :codigo, :descricao, :tipo, :valor, :data_inicio, :data_fim, :limite_uso, :limite_por_cliente, :ativo
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':codigo' => strtoupper(trim($data['codigo'])),
            ':descricao' => isset($data['descricao']) ? trim($data['descricao']) : null,
            ':tipo' => strtoupper(trim($data['tipo'] ?? 'PERCENTUAL')),
            ':valor' => (float)$data['valor'],
            ':data_inicio' => trim($data['data_inicio']),
            ':data_fim' => trim($data['data_fim']),
            ':limite_uso' => isset($data['limite_uso']) ? (int)$data['limite_uso'] : 0,
            ':limite_por_cliente' => isset($data['limite_por_cliente']) ? (int)$data['limite_por_cliente'] : 1,
            ':ativo' => isset($data['ativo']) ? (int)(bool)$data['ativo'] : 1,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Validate coupon applicability, usage limits, and calculate discount
    public function validateCupom(int $arenaId, string $codigo, ?int $clienteId, float $originalPrice): array
    {
        $coupon = $this->findByCode($arenaId, $codigo);
        if (!$coupon || (int)$coupon['ativo'] !== 1) {
            return [
                'valid' => false,
                'message' => 'Cupom de desconto invalido ou inativo.',
            ];
        }

        $today = date('Y-m-d');
        if ($today < $coupon['data_inicio'] || $today > $coupon['data_fim']) {
            return [
                'valid' => false,
                'message' => 'Cupom expirado ou fora do periodo de validade.',
            ];
        }

        $couponId = (int)$coupon['id'];

        // Check global usage limit (if set > 0)
        $limitGlobal = (int)$coupon['limite_uso'];
        if ($limitGlobal > 0) {
            $stmtCountGlobal = $this->pdo->prepare("SELECT COUNT(*) FROM `cupom_utilizacoes` WHERE `cupom_id` = :cupom_id");
            $stmtCountGlobal->execute([':cupom_id' => $couponId]);
            $usedTotal = (int)$stmtCountGlobal->fetchColumn();

            if ($usedTotal >= $limitGlobal) {
                return [
                    'valid' => false,
                    'message' => 'O limite total de utilizacoes deste cupom foi atingido.',
                ];
            }
        }

        // Check per-customer usage limit (if customer provided and limit > 0)
        if ($clienteId !== null) {
            $limitPerClient = (int)$coupon['limite_por_cliente'];
            if ($limitPerClient > 0) {
                $stmtCountClient = $this->pdo->prepare("SELECT COUNT(*) FROM `cupom_utilizacoes` WHERE `cupom_id` = :cupom_id AND `cliente_id` = :cliente_id");
                $stmtCountClient->execute([':cupom_id' => $couponId, ':cliente_id' => $clienteId]);
                $usedByClient = (int)$stmtCountClient->fetchColumn();

                if ($usedByClient >= $limitPerClient) {
                    return [
                        'valid' => false,
                        'message' => 'Voce ja atingiu o limite maximo de uso deste cupom.',
                    ];
                }
            }
        }

        // Calculate discount
        $tipo = $coupon['tipo'];
        $valorRegra = (float)$coupon['valor'];
        $desconto = 0.00;

        if ($tipo === 'PERCENTUAL') {
            $desconto = round(($originalPrice * $valorRegra) / 100.0, 2);
        } else {
            $desconto = round($valorRegra, 2);
        }

        $desconto = min($originalPrice, $desconto);
        $valorFinal = max(0.00, round($originalPrice - $desconto, 2));

        return [
            'valid' => true,
            'cupom' => $coupon,
            'desconto' => $desconto,
            'valor_original' => $originalPrice,
            'valor_final' => $valorFinal,
        ];
    }

    // Record coupon usage in cupom_utilizacoes table
    public function recordUsage(int $cupomId, int $arenaId, int $clienteId, int $agendamentoId, float $desconto): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO `cupom_utilizacoes` (
            `cupom_id`, `arena_id`, `cliente_id`, `agendamento_id`, `valor_desconto`
        ) VALUES (
            :cupom_id, :arena_id, :cliente_id, :agendamento_id, :valor_desconto
        )");

        return $stmt->execute([
            ':cupom_id' => $cupomId,
            ':arena_id' => $arenaId,
            ':cliente_id' => $clienteId,
            ':agendamento_id' => $agendamentoId,
            ':valor_desconto' => $desconto,
        ]);
    }

    // Update coupon details
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['codigo'])) {
            $fields[] = "`codigo` = :codigo";
            $params[':codigo'] = strtoupper(trim($data['codigo']));
        }
        if (array_key_exists('descricao', $data)) {
            $fields[] = "`descricao` = :descricao";
            $params[':descricao'] = $data['descricao'] !== null ? trim($data['descricao']) : null;
        }
        if (isset($data['tipo'])) {
            $fields[] = "`tipo` = :tipo";
            $params[':tipo'] = strtoupper(trim($data['tipo']));
        }
        if (isset($data['valor'])) {
            $fields[] = "`valor` = :valor";
            $params[':valor'] = (float)$data['valor'];
        }
        if (isset($data['data_inicio'])) {
            $fields[] = "`data_inicio` = :data_inicio";
            $params[':data_inicio'] = trim($data['data_inicio']);
        }
        if (isset($data['data_fim'])) {
            $fields[] = "`data_fim` = :data_fim";
            $params[':data_fim'] = trim($data['data_fim']);
        }
        if (isset($data['limite_uso'])) {
            $fields[] = "`limite_uso` = :limite_uso";
            $params[':limite_uso'] = (int)$data['limite_uso'];
        }
        if (isset($data['limite_por_cliente'])) {
            $fields[] = "`limite_por_cliente` = :limite_por_cliente";
            $params[':limite_por_cliente'] = (int)$data['limite_por_cliente'];
        }
        if (isset($data['ativo'])) {
            $fields[] = "`ativo` = :ativo";
            $params[':ativo'] = (int)(bool)$data['ativo'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `cupons` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Update coupon status (ativo / inativo)
    public function updateStatus(int $id, bool $ativo): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `cupons` SET `ativo` = :ativo WHERE `id` = :id");
        return $stmt->execute([
            ':id' => $id,
            ':ativo' => $ativo ? 1 : 0,
        ]);
    }

    // Delete or deactivate coupon
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `cupons` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}

