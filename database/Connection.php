<?php
// Master Arena SaaS - Database Connection Handler
// Comments strictly in ASCII only.

namespace Database;

use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private static ?PDO $instance = null;

    // Retrieve singleton PDO instance
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    // Create PDO connection with automatic fallback
    public static function createConnection(bool $ensureDatabaseExists = true): PDO
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
            return $pdo;
        } catch (PDOException $e) {
            // If unknown database and ensure is true, attempt to create it
            if ($ensureDatabaseExists && ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false)) {
                self::ensureDatabaseExists($config);
                return new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            }

            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    // Ensure database exists (used primarily in local development environment)
    private static function ensureDatabaseExists(array $config): void
    {
        $serverDsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['charset']
        );

        try {
            $pdo = new PDO(
                $serverDsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            $dbName = str_replace('`', '``', $config['database']);
            $sql = sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
                $dbName,
                $config['charset'],
                $config['collation']
            );

            $pdo->exec($sql);
        } catch (Throwable $e) {
            // Silently ignore if creation privileges are not granted on shared hosting
        }
    }

    // Reset instance (useful for testing or switching connection)
    public static function reset(): void
    {
        self::$instance = null;
    }
}
