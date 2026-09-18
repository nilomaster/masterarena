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

    // Create PDO connection with automatic database creation if requested
    public static function createConnection(bool $ensureDatabaseExists = true): PDO
    {
        $config = require __DIR__ . '/../config/database.php';

        if ($ensureDatabaseExists) {
            self::ensureDatabaseExists($config);
        }

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
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    // Ensure database exists before connecting to specific database
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
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to ensure database existence: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    // Reset instance (useful for testing or switching connection)
    public static function reset(): void
    {
        self::$instance = null;
    }
}
