<?php
// Master Arena SaaS - ValorHorario (Dynamic Pricing) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class ValorHorario
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find dynamic pricing rule by ID
    public function findById(int $id): ?array
    {
        $sql = "SELECT v.*, q.nome AS quadra_nome, m.nome AS modalidade_nome 
                FROM `valores_horarios` v 
                LEFT JOIN `quadras` q ON v.quadra_id = q.id 
                LEFT JOIN `modalidades` m ON v.modalidade_id = m.id 
                WHERE v.`id` = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // List dynamic pricing rules for an arena
    public function listByArena(int $arenaId, ?int $quadraId = null, ?int $modalidadeId = null): array
    {
        $sql = "SELECT v.*, q.nome AS quadra_nome, m.nome AS modalidade_nome 
                FROM `valores_horarios` v 
                LEFT JOIN `quadras` q ON v.quadra_id = q.id 
                LEFT JOIN `modalidades` m ON v.modalidade_id = m.id 
                WHERE v.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($quadraId !== null) {
            $sql .= " AND (v.`quadra_id` = :quadra_id OR v.`quadra_id` IS NULL)";
            $params[':quadra_id'] = $quadraId;
        }

        if ($modalidadeId !== null) {
            $sql .= " AND (v.`modalidade_id` = :modalidade_id OR v.`modalidade_id` IS NULL)";
            $params[':modalidade_id'] = $modalidadeId;
        }

        $sql .= " ORDER BY v.`dia_semana` ASC, v.`hora_inicio` ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    // Create a dynamic pricing rule
    public function create(int $arenaId, array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `valores_horarios` (
            `arena_id`, `quadra_id`, `modalidade_id`, `dia_semana`, `hora_inicio`, `hora_fim`, `valor`
        ) VALUES (
            :arena_id, :quadra_id, :modalidade_id, :dia_semana, :hora_inicio, :hora_fim, :valor
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':quadra_id' => !empty($data['quadra_id']) ? (int)$data['quadra_id'] : null,
            ':modalidade_id' => !empty($data['modalidade_id']) ? (int)$data['modalidade_id'] : null,
            ':dia_semana' => (int)$data['dia_semana'],
            ':hora_inicio' => substr(trim($data['hora_inicio']), 0, 8),
            ':hora_fim' => substr(trim($data['hora_fim']), 0, 8),
            ':valor' => (float)$data['valor'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Resolve active hourly price considering priority hierarchy
    public function resolvePrice(
        int $arenaId,
        int $quadraId,
        int $modalidadeId,
        int $dayOfWeek,
        string $horaInicio,
        string $horaFim,
        float $defaultPrice
    ): float {
        // Query rules that overlap this slot time range
        $sql = "SELECT * FROM `valores_horarios` 
                WHERE `arena_id` = :arena_id 
                  AND `dia_semana` = :dia_semana 
                  AND `hora_inicio` <= :hora_inicio 
                  AND `hora_fim` >= :hora_fim 
                ORDER BY 
                  CASE 
                    WHEN `quadra_id` = :quadra_id THEN 1 
                    WHEN `modalidade_id` = :modalidade_id THEN 2 
                    ELSE 3 
                  END ASC,
                  `id` DESC 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':arena_id' => $arenaId,
            ':dia_semana' => $dayOfWeek,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
            ':quadra_id' => $quadraId,
            ':modalidade_id' => $modalidadeId,
        ]);

        $rule = $stmt->fetch();
        if ($rule && isset($rule['valor'])) {
            return (float)$rule['valor'];
        }

        return $defaultPrice;
    }

    // Delete dynamic pricing rule
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `valores_horarios` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
