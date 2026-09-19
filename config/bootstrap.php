<?php
// Master Arena SaaS - Environment Loader Bootstrap
// Comments strictly in ASCII only.

if (!function_exists('loadMasterArenaEnv')) {
    function loadMasterArenaEnv(string $envPath): void
    {
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);

                // Strip surrounding single or double quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }

                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Load .env from project root with fallback to .env.example
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/.env')) {
    loadMasterArenaEnv($rootDir . '/.env');
} elseif (file_exists($rootDir . '/.env.example')) {
    loadMasterArenaEnv($rootDir . '/.env.example');
}
