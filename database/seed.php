<?php
// Master Arena SaaS - Database Seeder CLI Runner
// Comments strictly in ASCII only.

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/seeds/InitialDatabaseSeeder.php';

use Database\Connection;
use Database\Seeds\InitialDatabaseSeeder;

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

try {
    $pdo = Connection::getInstance();
    $seeder = new InitialDatabaseSeeder($pdo);
    $seeder->run();
} catch (Throwable $e) {
    echo "Seeding failed with error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    exit(1);
}
