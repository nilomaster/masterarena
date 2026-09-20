<?php
// Master Arena SaaS - CaixaMovimentacao (Cash In / Cash Out Movement) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class CaixaMovimentacao
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find movement by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT m.*, u.nome AS usuario_nome 
                FROM `caixa_movimentacoes` m 
                LEFT JOIN `usuarios` u ON m.usuario_id = u.id 
                WHERE m.`id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Register a new cash movement
    public function create(
        int $arenaId,
        int $caixaSessaoId,
        string $tipo,
        float $valor,
        string $motivo,
        int $usuarioId
    ): int {
        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, ['SUPRIMENTO', 'SANGRIA', 'DESPESA'], true)) {
            $tipo = 'SANGRIA';
        }

        $stmt = $this->pdo->prepare("INSERT INTO `caixa_movimentacoes` (
            `arena_id`, `caixa_sessao_id`, `tipo`, `valor`, `motivo`, `usuario_id`
        ) VALUES (
            :arena_id, :caixa_sessao_id, :tipo, :valor, :motivo, :usuario_id
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':caixa_sessao_id' => $caixaSessaoId,
            ':tipo' => $tipo,
            ':valor' => max(0.01, $valor),
            ':motivo' => trim($motivo),
            ':usuario_id' => $usuarioId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // List movements for a specific session
    public function listBySession(int $caixaSessaoId): array
    {
        $stmt = $this->pdo->prepare("SELECT m.*, u.nome AS usuario_nome 
                FROM `caixa_movimentacoes` m 
                LEFT JOIN `usuarios` u ON m.usuario_id = u.id 
                WHERE m.`caixa_sessao_id` = :sessao_id 
                ORDER BY m.`id` DESC");
        $stmt->execute([':sessao_id' => $caixaSessaoId]);

        return $stmt->fetchAll() ?: [];
    }

    // List movements for an arena with pagination
    public function listByArena(int $arenaId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT m.*, u.nome AS usuario_nome 
                FROM `caixa_movimentacoes` m 
                LEFT JOIN `usuarios` u ON m.usuario_id = u.id 
                WHERE m.`arena_id` = :arena_id 
                ORDER BY m.`id` DESC LIMIT :limit OFFSET :offset");

        $stmt->bindValue(':arena_id', $arenaId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
}
