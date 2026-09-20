<?php
// Master Arena SaaS - Quadra (Court/Pitch) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Quadra
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // List courts for an arena with optional modalidade and status filters
    public function listByArena(int $arenaId, ?int $modalidadeId = null, ?string $status = null): array
    {
        $sql = "SELECT q.*, m.nome AS modalidade_nome, m.icone AS modalidade_icone 
                FROM `quadras` q 
                LEFT JOIN `modalidades` m ON q.modalidade_id = m.id 
                WHERE q.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($modalidadeId !== null) {
            $sql .= " AND q.`modalidade_id` = :modalidade_id";
            $params[':modalidade_id'] = $modalidadeId;
        }

        if ($status !== null) {
            $sql .= " AND q.`status` = :status";
            $params[':status'] = strtoupper($status);
        }

        $sql .= " ORDER BY q.`nome` ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    // Find court by ID with linked modalidade info
    public function findById(int $id): ?array
    {
        $sql = "SELECT q.*, m.nome AS modalidade_nome, m.icone AS modalidade_icone 
                FROM `quadras` q 
                LEFT JOIN `modalidades` m ON q.modalidade_id = m.id 
                WHERE q.`id` = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Count courts by arena with optional status filter
    public function countByArena(int $arenaId, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM `quadras` WHERE `arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($status !== null) {
            $sql .= " AND `status` = :status";
            $params[':status'] = strtoupper($status);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    // Create a new court
    public function create(int $arenaId, array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `quadras` (
            `arena_id`, `modalidade_id`, `nome`, `descricao`, `capacidade`, `valor_padrao`, `status`
        ) VALUES (
            :arena_id, :modalidade_id, :nome, :descricao, :capacidade, :valor_padrao, :status
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':modalidade_id' => (int)$data['modalidade_id'],
            ':nome' => trim($data['nome']),
            ':descricao' => isset($data['descricao']) ? trim($data['descricao']) : null,
            ':capacidade' => isset($data['capacidade']) ? (int)$data['capacidade'] : 4,
            ':valor_padrao' => isset($data['valor_padrao']) ? (float)$data['valor_padrao'] : 0.00,
            ':status' => isset($data['status']) ? strtoupper($data['status']) : 'ATIVO',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Update court details
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedColumns = ['modalidade_id', 'nome', 'descricao', 'capacidade', 'valor_padrao', 'status'];

        foreach ($allowedColumns as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "`{$col}` = :{$col}";
                if ($col === 'modalidade_id' || $col === 'capacidade') {
                    $params[":{$col}"] = (int)$data[$col];
                } elseif ($col === 'valor_padrao') {
                    $params[":{$col}"] = (float)$data[$col];
                } elseif ($col === 'status') {
                    $params[":{$col}"] = strtoupper($data[$col]);
                } elseif ($data[$col] !== null) {
                    $params[":{$col}"] = trim((string)$data[$col]);
                } else {
                    $params[":{$col}"] = null;
                }
            }
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE `quadras` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    // Update operational status
    public function updateStatus(int $id, string $status): bool
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, ['ATIVO', 'MANUTENCAO', 'INATIVO'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE `quadras` SET `status` = :status WHERE `id` = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // Delete court
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `quadras` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
