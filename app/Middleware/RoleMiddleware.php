<?php
// Master Arena SaaS - Base Role Authorization Middleware
// Comments strictly in ASCII only.

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

abstract class RoleMiddleware
{
    protected array $allowedRoles = [];

    // Verify user profile against allowed roles
    public function handle(Request $request): void
    {
        $user = $request->getUser();

        if (!$user) {
            Response::unauthorized('Usuario nao autenticado.');
            return;
        }

        $userRole = strtoupper($user['perfil'] ?? '');

        // Superadmin has universal access
        if ($userRole === 'SUPERADMIN') {
            return;
        }

        if (!in_array($userRole, $this->allowedRoles, true)) {
            Response::forbidden('Acesso restrito. Seu perfil nao possui permissao para este recurso.');
            return;
        }
    }
}
