<?php
// Master Arena SaaS - Modalidade (Sport Type) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Modalidade
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // List all modalidades for a specific arena
    public function listByArena(int $arenaId, ?bool $onlyActive = null): array
    {
        $sql = "SELECT * FROM `modalidades` WHERE `arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($onlyActive !== null) {
            $sql .= " AND `ativo` = :ativo";
            $params[':ativo'] = $onlyActive ? 1 : 0;
        }

        $sql .= " ORDER BY `nome` ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    // Find modalidade by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `modalidades` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Check if sport name is available within the same arena
    public function isNameAvailable(int $arenaId, string $nome, ?int $exceptId = null): bool
    {
        $sql = "SELECT `id` FROM `modalidades` WHERE `arena_id` = :arena_id AND LOWER(`nome`) = LOWER(:nome)";
        $params = [
            ':arena_id' => $arenaId,
            ':nome' => trim($nome),
        ];

        if ($exceptId !== null) {
            $sql .= " AND `id` != :except_id";
            $params[':except_id'] = $exceptId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() === false;
    }

    // Check if modalidade has linked courts
    public function hasCourts(int $modalidadeId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `quadras` WHERE `modalidade_id` = :modalidade_id");
        $stmt->execute([':modalidade_id' => $modalidadeId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    // Count linked courts
    public function countCourts(int $modalidadeId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `quadras` WHERE `modalidade_id` = :modalidade_id");
        $stmt->execute([':modalidade_id' => $modalidadeId]);

        return (int)$stmt->fetchColumn();
    }

    // Create a new modalidade
    public function create(int $arenaId, array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `modalidades` (
            `arena_id`, `nome`, `descricao`, `icone`, `ativo`
        ) VALUES (
            :arena_id, :nome, :descricao, :icone, :ativo
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':nome' => trim($data['nome']),
            ':descricao' => isset($data['descricao']) ? trim($data['descricao']) : null,
            ':icone' => isset($data['icone']) ? trim($data['icone']) : null,
            ':ativo' => isset($data['ativo']) ? (int)(bool)$data['ativo'] : 1,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Update modalidade
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedColumns = ['nome', 'descricao', 'icone', 'ativo'];

        foreach ($allowedColumns as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "`{$col}` = :{$col}";
                if ($col === 'ativo') {
                    $params[":{$col}"] = (int)(bool)$data[$col];
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

        $sql = "UPDATE `modalidades` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    // Delete modalidade
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `modalidades` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
