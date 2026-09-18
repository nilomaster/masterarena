<?php
// Master Arena SaaS - Migration: Create Coupons and Promotions Tables
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // Table: cupons (Discount vouchers)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `cupons` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `codigo` VARCHAR(50) NOT NULL,
            `descricao` VARCHAR(255) NULL,
            `tipo` ENUM('PERCENTUAL', 'VALOR_FIXO') NOT NULL DEFAULT 'PERCENTUAL',
            `valor` DECIMAL(10,2) NOT NULL,
            `data_inicio` DATE NOT NULL,
            `data_fim` DATE NOT NULL,
            `limite_uso` INT NOT NULL DEFAULT 0,
            `limite_por_cliente` INT NOT NULL DEFAULT 1,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_arena_cupom_codigo` (`arena_id`, `codigo`),
            INDEX `idx_cupons_validade` (`arena_id`, `ativo`, `data_inicio`, `data_fim`),
            CONSTRAINT `fk_cupons_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Add foreign key constraint from agendamentos to cupons
        $pdo->exec("ALTER TABLE `agendamentos`
            ADD CONSTRAINT `fk_agendamentos_cupom` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`) ON DELETE SET NULL;");

        // Table: cupom_utilizacoes (Track usage of vouchers per client and booking)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `cupom_utilizacoes` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `cupom_id` BIGINT UNSIGNED NOT NULL,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `cliente_id` BIGINT UNSIGNED NOT NULL,
            `agendamento_id` BIGINT UNSIGNED NOT NULL,
            `valor_desconto` DECIMAL(10,2) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_cupom_cliente` (`cupom_id`, `cliente_id`),
            INDEX `idx_cupom_arena` (`arena_id`),
            CONSTRAINT `fk_utilizacoes_cupom` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_utilizacoes_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_utilizacoes_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_utilizacoes_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Table: promocoes (Promotional pricing rules)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `promocoes` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `nome` VARCHAR(150) NOT NULL,
            `descricao` TEXT NULL,
            `quadra_id` BIGINT UNSIGNED NULL,
            `modalidade_id` BIGINT UNSIGNED NULL,
            `dia_semana` TINYINT UNSIGNED NULL COMMENT '0-6 or NULL for all days',
            `hora_inicio` TIME NULL,
            `hora_fim` TIME NULL,
            `valor_promocional` DECIMAL(10,2) NOT NULL,
            `data_inicio` DATE NOT NULL,
            `data_fim` DATE NOT NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_promocoes_busca` (`arena_id`, `ativo`, `data_inicio`, `data_fim`),
            CONSTRAINT `fk_promocoes_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_promocoes_quadra` FOREIGN KEY (`quadra_id`) REFERENCES `quadras` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_promocoes_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE `agendamentos` DROP FOREIGN KEY `fk_agendamentos_cupom`;");
        $pdo->exec("DROP TABLE IF EXISTS `promocoes`;");
        $pdo->exec("DROP TABLE IF EXISTS `cupom_utilizacoes`;");
        $pdo->exec("DROP TABLE IF EXISTS `cupons`;");
    }
};
