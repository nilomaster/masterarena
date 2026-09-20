<?php
// Master Arena SaaS - Authentication API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\JwtService;

class AuthController extends BaseController
{
    private User $userModel;
    private Arena $arenaModel;
    private JwtService $jwtService;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->userModel = new User();
        $this->arenaModel = new Arena();
        $this->jwtService = new JwtService();
        $this->auditLogger = new AuditLogger();
    }

    // Authenticate user credentials and return JWT token
    public function login(Request $request): void
    {
        $this->validate($request, [
            'email' => 'required|email',
            'senha' => 'required',
        ]);

        $email = trim((string)$request->input('email'));
        $password = (string)$request->input('senha');

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['senha_hash'])) {
            $this->unauthorized('Credenciais invalidas. Verifique seu e-mail e senha.');
            return;
        }

        if (($user['status'] ?? '') !== 'ATIVO') {
            $this->forbidden('Sua conta de usuario encontra-se inativa ou bloqueada.');
            return;
        }

        $arena = null;
        if (!empty($user['arena_id'])) {
            $arena = $this->arenaModel->findById((int)$user['arena_id']);
            if (!$arena || !$this->arenaModel->isActive($arena)) {
                $this->forbidden('A arena vinculada a este usuario esta inativa ou bloqueada.');
                return;
            }
        }

        // Generate JWT token with user and tenant claims
        $payload = [
            'user_id' => (int)$user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil' => $user['perfil'],
            'arena_id' => $user['arena_id'] ? (int)$user['arena_id'] : null,
            'arena_slug' => $arena['slug'] ?? null,
        ];

        $token = $this->jwtService->encode($payload);

        // Update last login timestamp
        $this->userModel->updateLastLogin((int)$user['id']);

        // Log audit trail
        $this->auditLogger->logLogin(
            $user['arena_id'] ? (int)$user['arena_id'] : null,
            (int)$user['id'],
            $request->getIp(),
            $request->getUserAgent()
        );

        $safeUser = User::formatSafe($user);

        $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int)(getenv('JWT_EXPIRATION') ?: 86400),
            'user' => $safeUser,
            'arena' => $arena,
        ], 'Login realizado com sucesso.');
    }

    // Refresh existing valid token
    public function refresh(Request $request): void
    {
        $token = $request->getBearerToken() ?: (string)$request->input('token');

        if (empty($token)) {
            $this->unauthorized('Token nao fornecido para renovacao.');
            return;
        }

        $payload = $this->jwtService->decode($token);
        if ($payload === null || empty($payload['user_id'])) {
            $this->unauthorized('Token invalido ou expirado.');
            return;
        }

        $user = $this->userModel->findById((int)$payload['user_id']);
        if (!$user || ($user['status'] ?? '') !== 'ATIVO') {
            $this->unauthorized('Usuario inativo ou nao encontrado.');
            return;
        }

        $arena = null;
        if (!empty($user['arena_id'])) {
            $arena = $this->arenaModel->findById((int)$user['arena_id']);
            if (!$arena || !$this->arenaModel->isActive($arena)) {
                $this->forbidden('Arena vinculada inativa.');
                return;
            }
        }

        $newPayload = [
            'user_id' => (int)$user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil' => $user['perfil'],
            'arena_id' => $user['arena_id'] ? (int)$user['arena_id'] : null,
            'arena_slug' => $arena['slug'] ?? null,
        ];

        $newToken = $this->jwtService->encode($newPayload);

        $this->success([
            'token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => (int)(getenv('JWT_EXPIRATION') ?: 86400),
        ], 'Token renovado com sucesso.');
    }

    // Return currently authenticated user info
    public function me(Request $request): void
    {
        $user = $request->getUser();
        if (!$user) {
            $this->unauthorized('Nao autenticado.');
            return;
        }

        $this->success([
            'user' => $user,
        ], 'Dados do usuario autenticado.');
    }

    // Logout and record audit log
    public function logout(Request $request): void
    {
        $user = $request->getUser();
        if ($user) {
            $this->auditLogger->logLogout(
                $user['arena_id'] ? (int)$user['arena_id'] : null,
                (int)$user['id'],
                $request->getIp(),
                $request->getUserAgent()
            );
        }

        $this->success(null, 'Sessao encerrada com sucesso.');
    }
}
