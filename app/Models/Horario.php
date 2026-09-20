<?php
// Master Arena SaaS - Horario (Operating Hours) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class Horario
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // List operating hours for arena or specific court
    public function listByArena(int $arenaId, ?int $quadraId = null): array
    {
        $sql = "SELECT * FROM `horarios` WHERE `arena_id` = :arena_id";
        $params = [':arena_id' => $arenaId];

        if ($quadraId !== null) {
            $sql .= " AND `quadra_id` = :quadra_id";
            $params[':quadra_id'] = $quadraId;
        } else {
            $sql .= " AND `quadra_id` IS NULL";
        }

        $sql .= " ORDER BY `dia_semana` ASC, `hora_inicio` ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    // Get operating hours for a specific day of week (0=Sun, 1=Mon, ..., 6=Sat)
    public function getScheduleForDay(int $arenaId, int $dayOfWeek, ?int $quadraId = null): ?array
    {
        // First look for court-specific schedule if quadraId provided
        if ($quadraId !== null) {
            $stmtCourt = $this->pdo->prepare("SELECT * FROM `horarios` 
                WHERE `arena_id` = :arena_id AND `quadra_id` = :quadra_id AND `dia_semana` = :dia_semana AND `ativo` = 1 
                LIMIT 1");
            $stmtCourt->execute([
                ':arena_id' => $arenaId,
                ':quadra_id' => $quadraId,
                ':dia_semana' => $dayOfWeek,
            ]);
            $rowCourt = $stmtCourt->fetch();
            if ($rowCourt) {
                return $rowCourt;
            }
        }

        // Fallback to arena-wide schedule
        $stmtArena = $this->pdo->prepare("SELECT * FROM `horarios` 
            WHERE `arena_id` = :arena_id AND `quadra_id` IS NULL AND `dia_semana` = :dia_semana AND `ativo` = 1 
            LIMIT 1");
        $stmtArena->execute([
            ':arena_id' => $arenaId,
            ':dia_semana' => $dayOfWeek,
        ]);
        $rowArena = $stmtArena->fetch();

        return $rowArena ?: null;
    }

    // Save or replace weekly schedule (batch of days 0 to 6)
    public function saveWeeklySchedule(int $arenaId, array $days, ?int $quadraId = null): bool
    {
        $this->pdo->beginTransaction();

        try {
            // Delete existing schedules for this target
            if ($quadraId !== null) {
                $stmtDel = $this->pdo->prepare("DELETE FROM `horarios` WHERE `arena_id` = :arena_id AND `quadra_id` = :quadra_id");
                $stmtDel->execute([':arena_id' => $arenaId, ':quadra_id' => $quadraId]);
            } else {
                $stmtDel = $this->pdo->prepare("DELETE FROM `horarios` WHERE `arena_id` = :arena_id AND `quadra_id` IS NULL");
                $stmtDel->execute([':arena_id' => $arenaId]);
            }

            $stmtIns = $this->pdo->prepare("INSERT INTO `horarios` (
                `arena_id`, `quadra_id`, `dia_semana`, `hora_inicio`, `hora_fim`, `duracao_minutos`, `intervalo_minutos`, `ativo`
            ) VALUES (
                :arena_id, :quadra_id, :dia_semana, :hora_inicio, :hora_fim, :duracao_minutos, :intervalo_minutos, :ativo
            )");

            foreach ($days as $d) {
                $stmtIns->execute([
                    ':arena_id' => $arenaId,
                    ':quadra_id' => $quadraId,
                    ':dia_semana' => (int)$d['dia_semana'],
                    ':hora_inicio' => substr(trim($d['hora_inicio']), 0, 8),
                    ':hora_fim' => substr(trim($d['hora_fim']), 0, 8),
                    ':duracao_minutos' => isset($d['duracao_minutos']) ? (int)$d['duracao_minutos'] : 60,
                    ':intervalo_minutos' => isset($d['intervalo_minutos']) ? (int)$d['intervalo_minutos'] : 0,
                    ':ativo' => isset($d['ativo']) ? (int)(bool)$d['ativo'] : 1,
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
