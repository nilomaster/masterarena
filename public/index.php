<?php
// Master Arena SaaS - Public Web Entry Point
// Comments strictly in ASCII only.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../database/Connection.php';

use Database\Connection;

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Connection::getInstance();
    $dbStatus = 'connected';
} catch (Throwable $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

echo json_encode([
    'app' => 'MASTER ARENA SaaS',
    'status' => 'operational',
    'database' => $dbStatus,
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
