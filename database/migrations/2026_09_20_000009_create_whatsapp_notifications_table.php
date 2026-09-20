<?php
// Master Arena SaaS - Migration: Create WhatsApp Notifications Table
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `notificacoes_whatsapp` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `arena_id` BIGINT UNSIGNED NOT NULL,
            `cliente_id` BIGINT UNSIGNED NULL,
            `agendamento_id` BIGINT UNSIGNED NULL,
            `tipo` ENUM('PIX_PENDENTE', 'RESERVA_CONFIRMADA', 'LEMBRETE_JOGO', 'CANCELAMENTO', 'TESTE') NOT NULL,
            `telefone` VARCHAR(25) NOT NULL,
            `mensagem` TEXT NOT NULL,
            `status` ENUM('PENDENTE', 'ENVIADO', 'FALHA') NOT NULL DEFAULT 'PENDENTE',
            `gateway_provider` VARCHAR(50) NOT NULL DEFAULT 'SIMULATOR',
            `gateway_response` TEXT NULL,
            `enviado_em` DATETIME NULL,
            `tentativas` INT NOT NULL DEFAULT 0,
            `erro` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_notif_arena_tipo` (`arena_id`, `tipo`),
            INDEX `idx_notif_agendamento` (`agendamento_id`),
            INDEX `idx_notif_status` (`status`),
            INDEX `idx_notif_cliente` (`cliente_id`),
            CONSTRAINT `fk_notif_arena` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_notif_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_notif_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `notificacoes_whatsapp`;");
    }
};
