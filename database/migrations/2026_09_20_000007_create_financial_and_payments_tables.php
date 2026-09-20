<?php
// Master Arena SaaS - Migration: Create Financial, Payments & Cash Register Tables
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // 1. Table: caixa_sessoes (Cash register daily shifts / shifts control)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `caixa_sessoes` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `usuario_abertura_id` BIGINT UNSIGNED NOT NULL,
            `usuario_fechamento_id` BIGINT UNSIGNED NULL,
            `data_abertura` DATETIME NOT NULL,
            `data_fechamento` DATETIME NULL,
            `saldo_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `saldo_final_informado` DECIMAL(10,2) NULL,
            `saldo_final_sistema` DECIMAL(10,2) NULL,
            `diferenca` DECIMAL(10,2) NULL,
            `status` ENUM('ABERTO', 'FECHADO') NOT NULL DEFAULT 'ABERTO',
            `observacoes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_caixa_arena_status` (`arena_id`, `status`),
            CONSTRAINT `fk_caixa_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_caixa_usuario_abertura` FOREIGN KEY (`usuario_abertura_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_caixa_usuario_fechamento` FOREIGN KEY (`usuario_fechamento_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Table: caixa_movimentacoes (Cash register cash-in / cash-out / petty expenses)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `caixa_movimentacoes` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `caixa_sessao_id` BIGINT UNSIGNED NOT NULL,
            `tipo` ENUM('SUPRIMENTO', 'SANGRIA', 'DESPESA') NOT NULL,
            `valor` DECIMAL(10,2) NOT NULL,
            `motivo` VARCHAR(255) NOT NULL,
            `usuario_id` BIGINT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_mov_sessao` (`caixa_sessao_id`),
            INDEX `idx_mov_arena` (`arena_id`),
            CONSTRAINT `fk_mov_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_mov_sessao` FOREIGN KEY (`caixa_sessao_id`) REFERENCES `caixa_sessoes` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 3. Table: pagamentos (Financial transactions, bookings payments and revenue/expenses)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pagamentos` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `agendamento_id` BIGINT UNSIGNED NULL,
            `cliente_id` BIGINT UNSIGNED NULL,
            `caixa_sessao_id` BIGINT UNSIGNED NULL,
            `tipo` ENUM('RECEITA', 'DESPESA') NOT NULL DEFAULT 'RECEITA',
            `categoria` VARCHAR(100) NOT NULL DEFAULT 'RESERVA_QUADRA',
            `descricao` VARCHAR(255) NULL,
            `metodo_pagamento` ENUM('PIX', 'CARTAO_CREDITO', 'CARTAO_DEBITO', 'DINHEIRO', 'TRANSFERENCIA') NOT NULL DEFAULT 'PIX',
            `valor` DECIMAL(10,2) NOT NULL,
            `valor_taxa` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `valor_liquido` DECIMAL(10,2) NOT NULL,
            `status` ENUM('PENDENTE', 'PAGO', 'CANCELADO', 'ESTORNADO', 'EXPIRADO') NOT NULL DEFAULT 'PENDENTE',
            `gateway` VARCHAR(50) NULL DEFAULT 'INTERNO',
            `gateway_transaction_id` VARCHAR(191) NULL,
            `pix_copia_cola` TEXT NULL,
            `data_pagamento` DATETIME NULL,
            `data_expiracao` DATETIME NULL,
            `comprovante_url` VARCHAR(255) NULL,
            `criado_por` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_pagamentos_arena` (`arena_id`),
            INDEX `idx_pagamentos_agendamento` (`agendamento_id`),
            INDEX `idx_pagamentos_cliente` (`cliente_id`),
            INDEX `idx_pagamentos_caixa` (`caixa_sessao_id`),
            INDEX `idx_pagamentos_status` (`status`),
            INDEX `idx_pagamentos_data_pagamento` (`data_pagamento`),
            INDEX `idx_pagamentos_metodo` (`metodo_pagamento`),
            CONSTRAINT `fk_pagamentos_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pagamentos_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_pagamentos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_pagamentos_caixa` FOREIGN KEY (`caixa_sessao_id`) REFERENCES `caixa_sessoes` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_pagamentos_criador` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `pagamentos`;");
        $pdo->exec("DROP TABLE IF EXISTS `caixa_movimentacoes`;");
        $pdo->exec("DROP TABLE IF EXISTS `caixa_sessoes`;");
    }
};
