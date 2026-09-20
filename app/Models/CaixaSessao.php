<?php
// Master Arena SaaS - CaixaSessao (Cash Register Shift Session) Model
// Comments strictly in ASCII only.

namespace App\Models;

use Database\Connection;
use PDO;

class CaixaSessao
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    // Find session by ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT s.*, 
                    ua.nome AS usuario_abertura_nome, 
                    uf.nome AS usuario_fechamento_nome 
                FROM `caixa_sessoes` s
                LEFT JOIN `usuarios` ua ON s.usuario_abertura_id = ua.id
                LEFT JOIN `usuarios` uf ON s.usuario_fechamento_id = uf.id
                WHERE s.`id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Find currently open cash register session for an arena
    public function findOpenSession(int $arenaId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT s.*, 
                    ua.nome AS usuario_abertura_nome
                FROM `caixa_sessoes` s
                LEFT JOIN `usuarios` ua ON s.usuario_abertura_id = ua.id
                WHERE s.`arena_id` = :arena_id AND s.`status` = 'ABERTO' 
                ORDER BY s.`id` DESC LIMIT 1");
        $stmt->execute([':arena_id' => $arenaId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Open a new cash register shift
    public function open(int $arenaId, int $usuarioId, float $saldoInicial, ?string $observacoes = null): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO `caixa_sessoes` (
            `arena_id`, `usuario_abertura_id`, `data_abertura`, `saldo_inicial`, `status`, `observacoes`
        ) VALUES (
            :arena_id, :usuario_id, :data_abertura, :saldo_inicial, 'ABERTO', :observacoes
        )");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':usuario_id' => $usuarioId,
            ':data_abertura' => date('Y-m-d H:i:s'),
            ':saldo_inicial' => max(0.00, $saldoInicial),
            ':observacoes' => $observacoes ? trim($observacoes) : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Close a cash register shift
    public function close(
        int $id,
        int $usuarioId,
        float $saldoInformado,
        float $saldoSistema,
        float $diferenca,
        ?string $observacoes = null
    ): bool {
        $session = $this->findById($id);
        $obsAtual = $session['observacoes'] ?? '';
        $obsFinal = $obsAtual;
        if (!empty($observacoes)) {
            $obsFinal = !empty($obsAtual) ? $obsAtual . ' | Fechamento: ' . trim($observacoes) : 'Fechamento: ' . trim($observacoes);
        }

        $stmt = $this->pdo->prepare("UPDATE `caixa_sessoes` SET 
            `usuario_fechamento_id` = :usuario_id,
            `data_fechamento` = :data_fechamento,
            `saldo_final_informado` = :saldo_informado,
            `saldo_final_sistema` = :saldo_sistema,
            `diferenca` = :diferenca,
            `status` = 'FECHADO',
            `observacoes` = :observacoes
            WHERE `id` = :id AND `status` = 'ABERTO'");

        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId,
            ':data_fechamento' => date('Y-m-d H:i:s'),
            ':saldo_informado' => $saldoInformado,
            ':saldo_sistema' => $saldoSistema,
            ':diferenca' => $diferenca,
            ':observacoes' => !empty($obsFinal) ? $obsFinal : null,
        ]);
    }

    // Calculate current balance for an open session
    public function calculateCurrentBalance(int $sessionId): array
    {
        $session = $this->findById($sessionId);
        if (!$session) {
            return [
                'saldo_inicial' => 0.00,
                'total_dinheiro_recebido' => 0.00,
                'total_suprimentos' => 0.00,
                'total_sangrias' => 0.00,
                'total_despesas' => 0.00,
                'saldo_dinheiro_esperado' => 0.00,
                'total_outros_metodos' => 0.00,
            ];
        }

        $saldoInicial = (float)$session['saldo_inicial'];

        // Cash payments attached to this session
        $stmtCash = $this->pdo->prepare("SELECT COALESCE(SUM(valor), 0.00) 
            FROM `pagamentos` 
            WHERE `caixa_sessao_id` = :sessao_id 
              AND `status` = 'PAGO' 
              AND `tipo` = 'RECEITA' 
              AND `metodo_pagamento` = 'DINHEIRO'");
        $stmtCash->execute([':sessao_id' => $sessionId]);
        $dinheiroRecebido = (float)$stmtCash->fetchColumn();

        // Non-cash payments (PIX, Cards) attached to this session
        $stmtOther = $this->pdo->prepare("SELECT COALESCE(SUM(valor), 0.00) 
            FROM `pagamentos` 
            WHERE `caixa_sessao_id` = :sessao_id 
              AND `status` = 'PAGO' 
              AND `tipo` = 'RECEITA' 
              AND `metodo_pagamento` != 'DINHEIRO'");
        $stmtOther->execute([':sessao_id' => $sessionId]);
        $outrosMetodos = (float)$stmtOther->fetchColumn();

        // Cash register movements (Suprimentos, Sangrias, Despesas)
        $stmtMov = $this->pdo->prepare("SELECT `tipo`, COALESCE(SUM(valor), 0.00) AS total 
            FROM `caixa_movimentacoes` 
            WHERE `caixa_sessao_id` = :sessao_id 
            GROUP BY `tipo`");
        $stmtMov->execute([':sessao_id' => $sessionId]);
        $movRows = $stmtMov->fetchAll() ?: [];

        $suprimentos = 0.00;
        $sangrias = 0.00;
        $despesas = 0.00;

        foreach ($movRows as $m) {
            if ($m['tipo'] === 'SUPRIMENTO') $suprimentos = (float)$m['total'];
            if ($m['tipo'] === 'SANGRIA') $sangrias = (float)$m['total'];
            if ($m['tipo'] === 'DESPESA') $despesas = (float)$m['total'];
        }

        // Expected cash in drawer
        $saldoDinheiroEsperado = round($saldoInicial + $dinheiroRecebido + $suprimentos - $sangrias - $despesas, 2);

        return [
            'saldo_inicial' => $saldoInicial,
            'total_dinheiro_recebido' => $dinheiroRecebido,
            'total_suprimentos' => $suprimentos,
            'total_sangrias' => $sangrias,
            'total_despesas' => $despesas,
            'saldo_dinheiro_esperado' => $saldoDinheiroEsperado,
            'total_outros_metodos' => $outrosMetodos,
            'faturamento_total_sessao' => round($dinheiroRecebido + $outrosMetodos, 2),
        ];
    }

    // List shifts for an arena
    public function listByArena(int $arenaId, int $limit = 30, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT s.*, 
                    ua.nome AS usuario_abertura_nome, 
                    uf.nome AS usuario_fechamento_nome 
                FROM `caixa_sessoes` s
                LEFT JOIN `usuarios` ua ON s.usuario_abertura_id = ua.id
                LEFT JOIN `usuarios` uf ON s.usuario_fechamento_id = uf.id
                WHERE s.`arena_id` = :arena_id
                ORDER BY s.`id` DESC LIMIT :limit OFFSET :offset");

        $stmt->bindValue(':arena_id', $arenaId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
}
