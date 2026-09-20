<?php
// Master Arena SaaS - Optional Authentication Middleware (JWT)
// Comments strictly in ASCII only.

namespace App\Middleware;

use App\Core\Request;
use App\Models\Arena;
use App\Models\User;
use App\Services\JwtService;

class OptionalAuthMiddleware
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

    // Attach user to request context if valid bearer token is present
    public function handle(Request $request): void
    {
        $token = $request->getBearerToken();
        if (empty($token)) {
            return;
        }

        $payload = $this->jwtService->decode($token);
        if ($payload === null || empty($payload['user_id'])) {
            return;
        }

        $user = $this->userModel->findById((int)$payload['user_id']);
        if (!$user || ($user['status'] ?? '') !== 'ATIVO') {
            return;
        }

        if (!empty($user['arena_id'])) {
            $arena = $this->arenaModel->findById((int)$user['arena_id']);
            if (!$arena || !$this->arenaModel->isActive($arena)) {
                return;
            }
            $user['arena'] = $arena;
        }

        $safeUser = User::formatSafe($user);
        $request->setUser($safeUser);
    }
}
