<?php
// Master Arena SaaS - Authentication Middleware (JWT)
// Comments strictly in ASCII only.

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\Arena;
use App\Models\User;
use App\Services\JwtService;

class AuthMiddleware
{
    private JwtService $jwtService;
    private User $userModel;
    private Arena $arenaModel;

    public function __construct()
    {
        $this->jwtService = new JwtService();
        $this->userModel = new User();
        $this->arenaModel = new Arena();
    }

    // Intercept request and validate JWT bearer token
    public function handle(Request $request): void
    {
        $token = $request->getBearerToken();

        if (empty($token)) {
            Response::unauthorized('Token de autenticacao nao fornecido.');
            return;
        }

        $payload = $this->jwtService->decode($token);
        if ($payload === null || empty($payload['user_id'])) {
            Response::unauthorized('Token de autenticacao invalido ou expirado.');
            return;
        }

        $user = $this->userModel->findById((int)$payload['user_id']);
        if (!$user) {
            Response::unauthorized('Usuario nao encontrado.');
            return;
        }

        if (($user['status'] ?? '') !== 'ATIVO') {
            Response::unauthorized('Usuario inativo ou bloqueado.');
            return;
        }

        // If user belongs to an arena, verify arena status
        if (!empty($user['arena_id'])) {
            $arena = $this->arenaModel->findById((int)$user['arena_id']);
            if (!$arena || !$this->arenaModel->isActive($arena)) {
                Response::forbidden('A arena vinculada a esta conta encontra-se inativa ou bloqueada.');
                return;
            }
            $user['arena'] = $arena;
        }

        // Format user safely and attach to request context
        $safeUser = User::formatSafe($user);
        $request->setUser($safeUser);
    }
}
