<?php
// Master Arena SaaS - Migration: Add Checkin Fields to Bookings Table
// Comments strictly in ASCII only.

use Database\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        // 1. Check if codigo_checkin column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `agendamentos` LIKE 'codigo_checkin'");
        $exists = $stmt->fetch();

        if (!$exists) {
            $pdo->exec("ALTER TABLE `agendamentos` 
                ADD COLUMN `codigo_checkin` VARCHAR(20) NULL AFTER `status`,
                ADD COLUMN `checkin_em` DATETIME NULL AFTER `codigo_checkin`,
                ADD COLUMN `checkin_origem` VARCHAR(30) NULL DEFAULT 'PORTAL' AFTER `checkin_em`,
                ADD INDEX `idx_agendamento_checkin` (`arena_id`, `codigo_checkin`);");
        }

        // 2. Generate random checkin codes for existing bookings without one
        $selectStmt = $pdo->query("SELECT id, arena_id FROM `agendamentos` WHERE `codigo_checkin` IS NULL OR `codigo_checkin` = ''");
        $rows = $selectStmt->fetchAll() ?: [];

        $updateStmt = $pdo->prepare("UPDATE `agendamentos` SET `codigo_checkin` = :code WHERE `id` = :id");

        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $code = 'CHK-' . strtoupper(substr(md5(uniqid((string)$id, true)), 0, 6));
            $updateStmt->execute([
                ':code' => $code,
                ':id' => $id,
            ]);
        }
    }

    public function down(PDO $pdo): void
    {
        $stmt = $pdo->query("SHOW COLUMNS FROM `agendamentos` LIKE 'codigo_checkin'");
        $exists = $stmt->fetch();

        if ($exists) {
            $pdo->exec("ALTER TABLE `agendamentos` 
                DROP INDEX `idx_agendamento_checkin`,
                DROP COLUMN `codigo_checkin`,
                DROP COLUMN `checkin_em`,
                DROP COLUMN `checkin_origem`;");
        }
    }
};
