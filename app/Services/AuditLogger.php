<?php
// Master Arena SaaS - Audit Logger Service
// Comments strictly in ASCII only.

namespace App\Services;

use Database\Connection;
use PDO;
use Throwable;

class AuditLogger
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Insert an audit log record into logs table
    public function log(
        ?int $arenaId,
        ?int $userId,
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        $detalhes = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO `logs` 
                (`arena_id`, `usuario_id`, `acao`, `entidade`, `entidade_id`, `detalhes`, `ip`, `user_agent`) 
                VALUES 
                (:arena_id, :usuario_id, :acao, :entidade, :entidade_id, :detalhes, :ip, :user_agent)");

            $detalhesJson = null;
            if ($detalhes !== null) {
                $detalhesJson = is_string($detalhes) ? $detalhes : json_encode($detalhes, JSON_UNESCAPED_UNICODE);
            }

            $stmt->execute([
                ':arena_id' => $arenaId,
                ':usuario_id' => $userId,
                ':acao' => strtoupper($acao),
                ':entidade' => strtolower($entidade),
                ':entidade_id' => $entidadeId,
                ':detalhes' => $detalhesJson,
                ':ip' => substr($ip ?? '0.0.0.0', 0, 45),
                ':user_agent' => substr($userAgent ?? 'Unknown', 0, 255),
            ]);
        } catch (Throwable $e) {
            // Silently continue so logging does not block core business transactions
        }
    }

    // Log user login
    public function logLogin(?int $arenaId, int $userId, ?string $ip = null, ?string $userAgent = null): void
    {
        $this->log($arenaId, $userId, 'LOGIN', 'usuarios', $userId, ['message' => 'Autenticacao realizada com sucesso'], $ip, $userAgent);
    }

    // Log user logout
    public function logLogout(?int $arenaId, int $userId, ?string $ip = null, ?string $userAgent = null): void
    {
        $this->log($arenaId, $userId, 'LOGOUT', 'usuarios', $userId, ['message' => 'Sessao encerrada'], $ip, $userAgent);
    }
}
