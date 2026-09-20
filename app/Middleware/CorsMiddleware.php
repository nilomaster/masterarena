<?php
// Master Arena SaaS - CORS Middleware
// Comments strictly in ASCII only.

namespace App\Middleware;

use App\Core\Request;

class CorsMiddleware
{
    // Handle incoming request CORS headers
    public function handle(Request $request): void
    {
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-HTTP-Method-Override');
            header('Access-Control-Max-Age: 86400');
        }

        // Handle preflight OPTIONS request immediately
        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(204);
            if (!defined('MASTER_ARENA_TEST_MODE')) {
                exit;
            }
        }
    }
}
