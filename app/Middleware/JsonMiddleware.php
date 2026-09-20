<?php
// Master Arena SaaS - JSON Content-Type Middleware
// Comments strictly in ASCII only.

namespace App\Middleware;

use App\Core\Request;

class JsonMiddleware
{
    // Ensure response content-type is json
    public function handle(Request $request): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');

            // Send HTTP Strict Transport Security if request is on HTTPS
            if ($request->isSecure()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }
        }
    }
}
