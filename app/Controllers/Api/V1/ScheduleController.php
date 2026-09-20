<?php
// Master Arena SaaS - Schedule & Availability API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Arena;
use App\Models\Bloqueio;
use App\Models\Horario;
use App\Models\Quadra;
use App\Models\ValorHorario;
use App\Services\AuditLogger;
use App\Services\ScheduleService;

class ScheduleController extends BaseController
{
    private Arena $arenaModel;
    private Quadra $quadraModel;
    private Horario $horarioModel;
    private Bloqueio $bloqueioModel;
    private ValorHorario $valorHorarioModel;
    private ScheduleService $scheduleService;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->arenaModel = new Arena();
        $this->quadraModel = new Quadra();
        $this->horarioModel = new Horario();
        $this->bloqueioModel = new Bloqueio();
        $this->valorHorarioModel = new ValorHorario();
        $this->scheduleService = new ScheduleService();
        $this->auditLogger = new AuditLogger();
    }

    // Get court availability grid for a date (Public or Admin view)
    public function grade(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena nao encontrada.');
            return;
        }

        $currentUser = $request->getUser();
        $isPublicView = true;

        if ($currentUser && $this->canAccessArena($currentUser, $arenaId)) {
            $isPublicView = false;
        }

        $date = $request->query('data') ? (string)$request->query('data') : date('Y-m-d');
        $quadraId = $request->query('quadra_id') ? (int)$request->query('quadra_id') : null;
        $modalidadeId = $request->query('modalidade_id') ? (int)$request->query('modalidade_id') : null;

        $grid = $this->scheduleService->getAvailabilityGrid(
            $arenaId,
            $date,
            $quadraId,
            $modalidadeId,
            $isPublicView
        );

        $this->success([
            'arena' => [
                'id' => (int)$arena['id'],
                'nome_arena' => $arena['nome_arena'],
                'slug' => $arena['slug'],
            ],
            'grade' => $grid,
        ], 'Grade de disponibilidade recuperada com sucesso.');
    }

    // List operating hours configuration for an arena
    public function getHorarios(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao pode consultar horarios desta arena.');
            return;
        }

        $quadraId = $request->query('quadra_id') ? (int)$request->query('quadra_id') : null;
        $horarios = $this->horarioModel->listByArena($arenaId, $quadraId);

        $this->success([
            'arena_id' => $arenaId,
            'quadra_id' => $quadraId,
            'horarios' => $horarios,
        ], 'Horarios de funcionamento recuperados.');
    }

    // Save weekly operating hours
    public function saveHorarios(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao pode alterar horarios desta arena.');
            return;
        }

        $quadraId = $request->input('quadra_id') ? (int)$request->input('quadra_id') : null;
        $horarios = $request->input('horarios');

        if (!is_array($horarios)) {
            $this->validationError(['horarios' => ['O campo horarios deve ser uma lista com os dias da semana.']]);
            return;
        }

        $saved = $this->horarioModel->saveWeeklySchedule($arenaId, $horarios, $quadraId);

        if (!$saved) {
            $this->error('Falha ao salvar configuracao de horarios.', [], 500);
            return;
        }

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CONFIG_HORARIOS',
            'horarios',
            $arenaId,
            ['days_count' => count($horarios), 'quadra_id' => $quadraId],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([
            'arena_id' => $arenaId,
            'quadra_id' => $quadraId,
            'total_dias' => count($horarios),
        ], 'Grade semanal de funcionamento salva com sucesso.');
    }

    // List blocks for an arena
    public function listBloqueios(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao pode visualizar bloqueios desta arena.');
            return;
        }

        $quadraId = $request->query('quadra_id') ? (int)$request->query('quadra_id') : null;
        $dataInicio = $request->query('data_inicio') ? (string)$request->query('data_inicio') : null;
        $dataFim = $request->query('data_fim') ? (string)$request->query('data_fim') : null;

        $bloqueios = $this->bloqueioModel->listByArena($arenaId, $quadraId, $dataInicio, $dataFim);

        $this->success([
            'arena_id' => $arenaId,
            'bloqueios' => $bloqueios,
        ], 'Lista de bloqueios recuperada com sucesso.');
    }

    // Create a new court block
    public function createBloqueio(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para criar bloqueios nesta arena.');
            return;
        }

        $this->validate($request, [
            'quadra_id' => 'required|numeric',
            'data_inicio' => 'required',
            'hora_inicio' => 'required',
            'hora_fim' => 'required',
            'motivo' => 'required|min:3',
        ]);

        $quadraId = (int)$request->input('quadra_id');
        $court = $this->quadraModel->findById($quadraId);

        if (!$court || (int)$court['arena_id'] !== $arenaId) {
            $this->validationError(['quadra_id' => ['A quadra informada nao pertence a esta arena ou nao existe.']]);
            return;
        }

        $data = $request->all();
        $data['criado_por'] = $currentUser ? (int)$currentUser['id'] : null;

        $bloqueioId = $this->bloqueioModel->create($arenaId, $data);
        $newBlock = $this->bloqueioModel->findById($bloqueioId);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CREATE_BLOQUEIO',
            'bloqueios',
            $bloqueioId,
            ['quadra_id' => $quadraId, 'motivo' => $data['motivo']],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->created([
            'bloqueio' => $newBlock,
        ], 'Bloqueio de quadra cadastrado com sucesso.');
    }

    // Delete a court block
    public function deleteBloqueio(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $block = $this->bloqueioModel->findById($id);
        if (!$block) {
            $this->notFound('Bloqueio nao encontrado.');
            return;
        }

        if (!$this->canAccessArena($currentUser, (int)$block['arena_id'])) {
            $this->forbidden('Acesso negado. Voce nao pode remover bloqueios desta arena.');
            return;
        }

        $this->bloqueioModel->delete($id);

        $this->auditLogger->log(
            (int)$block['arena_id'],
            $currentUser ? (int)$currentUser['id'] : null,
            'DELETE_BLOQUEIO',
            'bloqueios',
            $id,
            ['motivo' => $block['motivo']],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([], 'Bloqueio removido com sucesso.');
    }

    // List dynamic pricing rules
    public function listValores(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao pode visualizar regras de precos desta arena.');
            return;
        }

        $quadraId = $request->query('quadra_id') ? (int)$request->query('quadra_id') : null;
        $modalidadeId = $request->query('modalidade_id') ? (int)$request->query('modalidade_id') : null;

        $valores = $this->valorHorarioModel->listByArena($arenaId, $quadraId, $modalidadeId);

        $this->success([
            'arena_id' => $arenaId,
            'valores' => $valores,
        ], 'Regras de precificacao dinamica recuperadas.');
    }

    // Create dynamic pricing rule
    public function createValor(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $currentUser = $request->getUser();

        if (!$this->canAccessArena($currentUser, $arenaId)) {
            $this->forbidden('Acesso negado. Voce nao possui permissao para criar regras de preco nesta arena.');
            return;
        }

        $this->validate($request, [
            'dia_semana' => 'required|numeric',
            'hora_inicio' => 'required',
            'hora_fim' => 'required',
            'valor' => 'required|numeric',
        ]);

        $data = $request->all();

        // If quadra_id passed, verify ownership
        if (!empty($data['quadra_id'])) {
            $court = $this->quadraModel->findById((int)$data['quadra_id']);
            if (!$court || (int)$court['arena_id'] !== $arenaId) {
                $this->validationError(['quadra_id' => ['A quadra informada nao pertence a esta arena.']]);
                return;
            }
        }

        $valorId = $this->valorHorarioModel->create($arenaId, $data);
        $newRule = $this->valorHorarioModel->findById($valorId);

        $this->auditLogger->log(
            $arenaId,
            $currentUser ? (int)$currentUser['id'] : null,
            'CREATE_VALOR_HORARIO',
            'valores_horarios',
            $valorId,
            ['valor' => (float)$data['valor'], 'dia_semana' => (int)$data['dia_semana']],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->created([
            'valor_horario' => $newRule,
        ], 'Regra de precificacao dinamica criada com sucesso.');
    }

    // Delete dynamic pricing rule
    public function deleteValor(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $currentUser = $request->getUser();

        $rule = $this->valorHorarioModel->findById($id);
        if (!$rule) {
            $this->notFound('Regra de precificacao nao encontrada.');
            return;
        }

        if (!$this->canAccessArena($currentUser, (int)$rule['arena_id'])) {
            $this->forbidden('Acesso negado. Voce nao pode remover regras desta arena.');
            return;
        }

        $this->valorHorarioModel->delete($id);

        $this->auditLogger->log(
            (int)$rule['arena_id'],
            $currentUser ? (int)$currentUser['id'] : null,
            'DELETE_VALOR_HORARIO',
            'valores_horarios',
            $id,
            [],
            $request->getIp(),
            $request->getUserAgent()
        );

        $this->success([], 'Regra de preco excluida com sucesso.');
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
