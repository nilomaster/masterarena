<?php
// Master Arena SaaS - Cupom (Discount Coupon) API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\Cliente;
use App\Models\Cupom;
use App\Services\AuditLogger;

class CupomController extends BaseController
{
    private Cupom $cupomModel;
    private Arena $arenaModel;
    private Cliente $clienteModel;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->cupomModel = new Cupom();
        $this->arenaModel = new Arena();
        $this->clienteModel = new Cliente();
        $this->auditLogger = new AuditLogger();
    }

    // List coupons for an arena (Admin / Manager)
    public function index(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para gerenciar cupons desta arena.');
            return;
        }

        $onlyActive = null;
        if ($request->query('ativo') !== null) {
            $onlyActive = filter_var($request->query('ativo'), FILTER_VALIDATE_BOOLEAN);
        }

        $cupons = $this->cupomModel->listByArena($arenaId, $onlyActive);

        $this->success([
            'arena_id' => $arenaId,
            'total' => count($cupons),
            'cupons' => $cupons,
        ]);
    }

    // Show single coupon
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $coupon = $this->cupomModel->findById($id);

        if (!$coupon) {
            $this->notFound('Cupom nao encontrado.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, (int)$coupon['arena_id'])) {
            $this->forbidden('Acesso negado para visualizar este cupom.');
            return;
        }

        $this->success(['cupom' => $coupon]);
    }

    // Create a new coupon (Admin / Manager)
    public function create(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para criar cupons nesta arena.');
            return;
        }

        $data = $request->getBody();

        // Validation
        $codigo = strtoupper(trim((string)($data['codigo'] ?? '')));
        if (empty($codigo)) {
            $this->badRequest('Codigo do cupom e obrigatorio.');
            return;
        }

        // Check if code already exists in this arena
        $existing = $this->cupomModel->findByCode($arenaId, $codigo);
        if ($existing) {
            $this->conflict('Ja existe um cupom com este codigo nesta arena.');
            return;
        }

        $tipo = strtoupper(trim((string)($data['tipo'] ?? 'PERCENTUAL')));
        if (!in_array($tipo, ['PERCENTUAL', 'VALOR_FIXO'], true)) {
            $this->badRequest('Tipo de cupom invalido. Valores aceitos: PERCENTUAL ou VALOR_FIXO.');
            return;
        }

        $valor = (float)($data['valor'] ?? 0);
        if ($valor <= 0) {
            $this->badRequest('Valor do desconto deve ser maior que zero.');
            return;
        }

        if ($tipo === 'PERCENTUAL' && $valor > 100) {
            $this->badRequest('Desconto percentual nao pode ser superior a 100%.');
            return;
        }

        $dataInicio = trim((string)($data['data_inicio'] ?? date('Y-m-d')));
        $dataFim = trim((string)($data['data_fim'] ?? date('Y-m-d', strtotime('+30 days'))));

        if ($dataFim < $dataInicio) {
            $this->badRequest('A data final de validade nao pode ser anterior a data inicial.');
            return;
        }

        $couponData = [
            'codigo' => $codigo,
            'descricao' => isset($data['descricao']) ? trim((string)$data['descricao']) : null,
            'tipo' => $tipo,
            'valor' => $valor,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'limite_uso' => isset($data['limite_uso']) ? (int)$data['limite_uso'] : 0,
            'limite_por_cliente' => isset($data['limite_por_cliente']) ? (int)$data['limite_por_cliente'] : 1,
            'ativo' => isset($data['ativo']) ? (int)(bool)$data['ativo'] : 1,
        ];

        $couponId = $this->cupomModel->create($arenaId, $couponData);
        $newCoupon = $this->cupomModel->findById($couponId);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CREATE_CUPOM',
            'cupons',
            $couponId,
            ['codigo' => $codigo, 'tipo' => $tipo, 'valor' => $valor],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->created(['cupom' => $newCoupon], 'Cupom criado com sucesso.');
    }

    // Update coupon details
    public function update(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $coupon = $this->cupomModel->findById($id);

        if (!$coupon) {
            $this->notFound('Cupom nao encontrado.');
            return;
        }

        $arenaId = (int)$coupon['arena_id'];
        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para alterar este cupom.');
            return;
        }

        $data = $request->getBody();
        $updateData = [];

        if (isset($data['codigo'])) {
            $newCode = strtoupper(trim((string)$data['codigo']));
            if (empty($newCode)) {
                $this->badRequest('Codigo do cupom nao pode ser vazio.');
                return;
            }
            if ($newCode !== $coupon['codigo']) {
                $existing = $this->cupomModel->findByCode($arenaId, $newCode);
                if ($existing) {
                    $this->conflict('Ja existe um cupom com este codigo nesta arena.');
                    return;
                }
            }
            $updateData['codigo'] = $newCode;
        }

        if (array_key_exists('descricao', $data)) {
            $updateData['descricao'] = $data['descricao'] !== null ? trim((string)$data['descricao']) : null;
        }

        if (isset($data['tipo'])) {
            $tipo = strtoupper(trim((string)$data['tipo']));
            if (!in_array($tipo, ['PERCENTUAL', 'VALOR_FIXO'], true)) {
                $this->badRequest('Tipo invalido. Valores aceitos: PERCENTUAL ou VALOR_FIXO.');
                return;
            }
            $updateData['tipo'] = $tipo;
        }

        if (isset($data['valor'])) {
            $valor = (float)$data['valor'];
            if ($valor <= 0) {
                $this->badRequest('Valor do desconto deve ser maior que zero.');
                return;
            }
            $tipoAtual = $updateData['tipo'] ?? $coupon['tipo'];
            if ($tipoAtual === 'PERCENTUAL' && $valor > 100) {
                $this->badRequest('Desconto percentual nao pode ultrapassar 100%.');
                return;
            }
            $updateData['valor'] = $valor;
        }

        if (isset($data['data_inicio'])) {
            $updateData['data_inicio'] = trim((string)$data['data_inicio']);
        }
        if (isset($data['data_fim'])) {
            $updateData['data_fim'] = trim((string)$data['data_fim']);
        }

        $inicio = $updateData['data_inicio'] ?? $coupon['data_inicio'];
        $fim = $updateData['data_fim'] ?? $coupon['data_fim'];
        if ($fim < $inicio) {
            $this->badRequest('A data final de validade nao pode ser anterior a data inicial.');
            return;
        }

        if (isset($data['limite_uso'])) {
            $updateData['limite_uso'] = max(0, (int)$data['limite_uso']);
        }
        if (isset($data['limite_por_cliente'])) {
            $updateData['limite_por_cliente'] = max(0, (int)$data['limite_por_cliente']);
        }
        if (isset($data['ativo'])) {
            $updateData['ativo'] = (int)(bool)$data['ativo'];
        }

        $this->cupomModel->update($id, $updateData);
        $updatedCoupon = $this->cupomModel->findById($id);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'UPDATE_CUPOM',
            'cupons',
            $id,
            $updateData,
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success(['cupom' => $updatedCoupon], 'Cupom atualizado com sucesso.');
    }

    // Toggle coupon status (active/inactive)
    public function updateStatus(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $coupon = $this->cupomModel->findById($id);

        if (!$coupon) {
            $this->notFound('Cupom nao encontrado.');
            return;
        }

        $arenaId = (int)$coupon['arena_id'];
        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para alterar o status deste cupom.');
            return;
        }

        $data = $request->getBody();
        if (!isset($data['ativo'])) {
            $this->badRequest('Campo ativo e obrigatorio (boolean).');
            return;
        }

        $ativo = (bool)$data['ativo'];
        $this->cupomModel->updateStatus($id, $ativo);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'UPDATE_CUPOM_STATUS',
            'cupons',
            $id,
            ['ativo' => $ativo],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success(['ativo' => $ativo], 'Status do cupom alterado com sucesso.');
    }

    // Delete coupon
    public function delete(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $coupon = $this->cupomModel->findById($id);

        if (!$coupon) {
            $this->notFound('Cupom nao encontrado.');
            return;
        }

        $arenaId = (int)$coupon['arena_id'];
        $currentUser = $request->getUser();
        if (!$currentUser || !$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado para remover este cupom.');
            return;
        }

        try {
            $this->cupomModel->delete($id);
        } catch (\PDOException $e) {
            // If already used in agendamentos, foreign key prevents hard deletion -> soft deactivate
            $this->cupomModel->updateStatus($id, false);
            $this->success([], 'Cupom ja utilizado em reservas anteriores. O cupom foi inativado.');
            return;
        }

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'DELETE_CUPOM',
            'cupons',
            $id,
            ['codigo' => $coupon['codigo']],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([], 'Cupom removido com sucesso.');
    }

    // Validate coupon applicability and simulate discount calculation
    public function validar(Request $request): void
    {

        $arenaId = (int)$request->getParam('arena_id');

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $data = $request->getBody();
        $codigo = strtoupper(trim((string)($data['codigo'] ?? '')));
        $precoOriginal = (float)($data['preco_original'] ?? $data['valor_total'] ?? 0);

        if (empty($codigo)) {
            $this->badRequest('Codigo do cupom e obrigatorio.');
            return;
        }

        if ($precoOriginal <= 0) {
            $this->badRequest('Preco original deve ser informado e maior que zero.');
            return;
        }

        // Optional customer identification
        $clienteId = null;
        if (!empty($data['cliente_id'])) {
            $clienteId = (int)$data['cliente_id'];
        } elseif (!empty($data['cliente_telefone'])) {
            $foundClient = $this->clienteModel->findByTelefone($arenaId, (string)$data['cliente_telefone']);
            if ($foundClient) {
                $clienteId = (int)$foundClient['id'];
            }
        }

        $validation = $this->cupomModel->validateCupom($arenaId, $codigo, $clienteId, $precoOriginal);

        if (!$validation['valid']) {
            $this->badRequest($validation['message']);
            return;
        }

        $coupon = $validation['cupom'];

        $this->success([
            'valido' => true,
            'cupom_id' => (int)$coupon['id'],
            'codigo' => $coupon['codigo'],
            'tipo' => $coupon['tipo'],
            'valor_regra' => (float)$coupon['valor'],
            'valor_original' => $validation['valor_original'],
            'desconto' => $validation['desconto'],
            'valor_final' => $validation['valor_final'],
        ], 'Cupom valido e aplicado com sucesso.');
    }

    // Helper to verify multi-tenant access rights
    private function canAccessArena(?array $user, int $targetArenaId): bool
    {
        if (!$user) {
            return false;
        }

        $perfil = strtoupper($user['perfil'] ?? '');
        if ($perfil === 'SUPERADMIN') {
            return true;
        }

        $userArenaId = isset($user['arena_id']) ? (int)$user['arena_id'] : null;
        return $userArenaId === $targetArenaId;
    }
}
