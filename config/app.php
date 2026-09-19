<?php
// Master Arena SaaS - App Configuration
// Comments strictly in ASCII only.

require_once __DIR__ . '/bootstrap.php';

date_default_timezone_set('America/Sao_Paulo');

return [
    'name' => 'MASTER ARENA',
    'company' => 'MasterDev Solutions',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => (getenv('APP_DEBUG') ?: 'true') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost/masterarena',
    'api_url' => getenv('API_URL') ?: 'http://localhost/masterarena/api/v1',
    'timezone' => 'America/Sao_Paulo',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'masterarena_secret_key_change_in_production_2026',
    'jwt_expiration' => (int)(getenv('JWT_EXPIRATION') ?: 86400),
];
