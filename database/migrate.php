<?php
// Master Arena SaaS - Migration Runner CLI
// Comments strictly in ASCII only.

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Migration.php';

use Database\Connection;

// Ensure CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->migrationsDir = __DIR__ . '/migrations';
        $this->ensureMigrationsTable();
    }

    // Create migrations control table if missing
    private function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `batch` INT NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->pdo->exec($sql);
    }

    // Get all migration files from directory
    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsDir . '/*.php');
        sort($files);
        return $files;
    }

    // Get list of applied migration names
    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT `migration` FROM `migrations` ORDER BY `id` ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get next batch number
    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(`batch`) as max_batch FROM `migrations`");
        $max = $stmt->fetchColumn();
        return ($max !== null && $max !== false) ? ((int)$max + 1) : 1;
    }

    // Execute pending migrations (UP)
    public function up(): void
    {
        echo "====================================================\n";
        echo " MASTER ARENA - Running Migrations (UP)\n";
        echo "====================================================\n";

        $files = $this->getMigrationFiles();
        $applied = $this->getAppliedMigrations();
        $batch = $this->getNextBatchNumber();
        $count = 0;

        foreach ($files as $filePath) {
            $fileName = basename($filePath, '.php');

            if (in_array($fileName, $applied, true)) {
                continue;
            }

            echo "Migrating: {$fileName} ... ";
            $migration = require $filePath;

            try {
                $migration->up($this->pdo);

                $stmt = $this->pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch)");
                $stmt->execute([
                    ':migration' => $fileName,
                    ':batch' => $batch,
                ]);

                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }
                echo "[OK]\n";
                $count++;
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                echo "[FAILED]\n";
                echo "Error: " . $e->getMessage() . "\n";
                echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
                exit(1);
            }
        }

        if ($count === 0) {
            echo "Nothing to migrate. Database is already up to date.\n";
        } else {
            echo "Successfully applied {$count} migration(s) (Batch #{$batch}).\n";
        }
    }

    // Rollback last migration batch (DOWN)
    public function down(): void
    {
        echo "====================================================\n";
        echo " MASTER ARENA - Rolling Back Migrations (DOWN)\n";
        echo "====================================================\n";

        $stmt = $this->pdo->query("SELECT MAX(`batch`) as max_batch FROM `migrations`");
        $lastBatch = $stmt->fetchColumn();

        if ($lastBatch === null || $lastBatch === false) {
            echo "No migrations found to revert.\n";
            return;
        }

        $stmt = $this->pdo->prepare("SELECT `migration` FROM `migrations` WHERE `batch` = :batch ORDER BY `id` DESC");
        $stmt->execute([':batch' => $lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($migrations as $fileName) {
            $filePath = $this->migrationsDir . '/' . $fileName . '.php';

            if (!file_exists($filePath)) {
                echo "Warning: Migration file {$fileName}.php not found. Skipping rollback file.\n";
                continue;
            }

            echo "Rolling back: {$fileName} ... ";
            $migration = require $filePath;

            try {
                $migration->down($this->pdo);

                $delStmt = $this->pdo->prepare("DELETE FROM `migrations` WHERE `migration` = :migration");
                $delStmt->execute([':migration' => $fileName]);

                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }
                echo "[OK]\n";
                $count++;
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                echo "[FAILED]\n";
                echo "Error: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        echo "Reverted {$count} migration(s) from Batch #{$lastBatch}.\n";
    }

    // Display status of all migrations
    public function status(): void
    {
        echo "====================================================\n";
        echo " MASTER ARENA - Migration Status\n";
        echo "====================================================\n";

        $files = $this->getMigrationFiles();
        $appliedStmt = $this->pdo->query("SELECT `migration`, `batch`, `executed_at` FROM `migrations`");
        $appliedMap = [];
        while ($row = $appliedStmt->fetch()) {
            $appliedMap[$row['migration']] = $row;
        }

        printf("%-55s | %-8s | %-6s | %-20s\n", "Migration", "Status", "Batch", "Executed At");
        echo str_repeat("-", 95) . "\n";

        foreach ($files as $filePath) {
            $fileName = basename($filePath, '.php');
            if (isset($appliedMap[$fileName])) {
                $info = $appliedMap[$fileName];
                printf("%-55s | \033[32m%-8s\033[0m | %-6d | %-20s\n", $fileName, "APPLIED", $info['batch'], $info['executed_at']);
            } else {
                printf("%-55s | \033[33m%-8s\033[0m | %-6s | %-20s\n", $fileName, "PENDING", "-", "-");
            }
        }
    }
}

// Command dispatcher
$action = $argv[1] ?? 'up';
$runner = new MigrationRunner();

switch (strtolower($action)) {
    case 'up':
        $runner->up();
        break;
    case 'down':
        $runner->down();
        break;
    case 'status':
        $runner->status();
        break;
    default:
        echo "Usage: php database/migrate.php [up|down|status]\n";
        exit(1);
}
