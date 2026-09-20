<?php
// Master Arena SaaS - Bloqueio (Court Block) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Bloqueio
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find block by ID
    public function findById(int $id): ?array
    {
        $sql = "SELECT b.*, q.nome AS quadra_nome 
                FROM `bloqueios` b 
                LEFT JOIN `quadras` q ON b.quadra_id = q.id 
                WHERE b.`id` = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // List blocks for an arena with optional filters
    public function listByArena(int $arenaId, ?int $quadraId = null, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql = "SELECT b.*, q.nome AS quadra_nome 
                FROM `bloqueios` b 
                LEFT JOIN `quadras` q ON b.quadra_id = q.id 
                WHERE b.`arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($quadraId !== null) {
            $sql .= " AND b.`quadra_id` = :quadra_id";
            $params[':quadra_id'] = $quadraId;
        }

        if ($dataInicio !== null) {
            $sql .= " AND b.`data_fim` >= :data_inicio";
            $params[':data_inicio'] = $dataInicio;
        }

        if ($dataFim !== null) {
            $sql .= " AND b.`data_inicio` <= :data_fim";
            $params[':data_fim'] = $dataFim;
        }

        $sql .= " ORDER BY b.`data_inicio` DESC, b.`hora_inicio` ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    // Create a new court block
    public function create(int $arenaId, array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `bloqueios` (
            `arena_id`, `quadra_id`, `data_inicio`, `data_fim`, `hora_inicio`, `hora_fim`, `motivo`, `criado_por`
        ) VALUES (
            :arena_id, :quadra_id, :data_inicio, :data_fim, :hora_inicio, :hora_fim, :motivo, :criado_por
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':quadra_id' => (int)$data['quadra_id'],
            ':data_inicio' => trim($data['data_inicio']),
            ':data_fim' => isset($data['data_fim']) ? trim($data['data_fim']) : trim($data['data_inicio']),
            ':hora_inicio' => substr(trim($data['hora_inicio']), 0, 8),
            ':hora_fim' => substr(trim($data['hora_fim']), 0, 8),
            ':motivo' => trim($data['motivo']),
            ':criado_por' => isset($data['criado_por']) ? (int)$data['criado_por'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Check if court has an active block overlapping given date and time range
    public function isTimeBlocked(int $arenaId, int $quadraId, string $date, string $horaInicio, string $horaFim): ?array
    {
        $sql = "SELECT b.*, q.nome AS quadra_nome 
                FROM `bloqueios` b 
                LEFT JOIN `quadras` q ON b.quadra_id = q.id 
                WHERE b.`arena_id` = :arena_id 
                  AND b.`quadra_id` = :quadra_id 
                  AND b.`data_inicio` <= :data AND b.`data_fim` >= :data 
                  AND b.`hora_inicio` < :hora_fim AND b.`hora_fim` > :hora_inicio 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':arena_id' => $arenaId,
            ':quadra_id' => $quadraId,
            ':data' => $date,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Delete a block
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `bloqueios` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
