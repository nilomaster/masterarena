<?php
// Master Arena SaaS - Customer Portal & Totem API Controller
// Comments strictly in ASCII only.

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Models\Agendamento;
use App\Models\Arena;
use App\Models\Modalidade;
use App\Models\Pagamento;
use App\Models\Quadra;
use App\Services\CheckinService;

class PortalController extends BaseController
{
    private Arena $arenaModel;
    private Modalidade $modalidadeModel;
    private Quadra $quadraModel;
    private Agendamento $agendamentoModel;
    private Pagamento $pagamentoModel;
    private CheckinService $checkinService;

    public function __construct()
    {
        $this->arenaModel = new Arena();
        $this->modalidadeModel = new Modalidade();
        $this->quadraModel = new Quadra();
        $this->agendamentoModel = new Agendamento();
        $this->pagamentoModel = new Pagamento();
        $this->checkinService = new CheckinService();
    }

    // Consolidated public data payload for fast loading of the customer portal
    public function getPortalInfo(Request $request): void
    {
        $identifier = $request->getParam('arena_id') ?? $request->getParam('slug');

        $arena = null;
        if (is_numeric($identifier)) {
            $arena = $this->arenaModel->findById((int)$identifier);
        } else {
            $arena = $this->arenaModel->findBySlug((string)$identifier);
        }

        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $arenaId = (int)$arena['id'];

        // Fetch active sports
        $modalidades = $this->modalidadeModel->listByArena($arenaId, true);

        // Fetch active courts
        $quadras = $this->quadraModel->listByArena($arenaId, null, 'ATIVO');

        // Fetch public settings
        $settings = $this->arenaModel->getSettings($arenaId);

        $arenaNome = $arena['nome_arena'] ?? $arena['nome_fantasia'] ?? 'Arena Esportiva';

        $this->ok([
            'arena' => [
                'id' => $arena['id'],
                'nome' => $arenaNome,
                'slug' => $arena['slug'],
                'telefone' => $arena['telefone'],
                'email' => $arena['email'],
                'endereco' => $arena['endereco'],
                'cidade' => $arena['cidade'],
                'estado' => $arena['estado'],
                'cep' => $arena['cep'],
                'logo' => $arena['logo'] ?? null,
                'status' => $arena['status'],
            ],
            'modalidades' => $modalidades,
            'quadras' => $quadras,
            'settings' => [
                'chave_pix' => !empty($settings['chave_pix'] ?? '') ? 'CONFIGURADA' : null,
                'titular_pix' => $settings['titular_pix'] ?? $arenaNome,
                'tempo_tolerancia_minutos' => $settings['tempo_tolerancia_minutos'] ?? 15,
                'antecedencia_maxima_dias' => $settings['antecedencia_maxima_dias'] ?? 30,
            ],
        ], 'Dados do portal carregados com sucesso.');
    }

    // Public lookup for customer bookings by WhatsApp phone
    public function getMyBookings(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $body = $request->getBody();
        $telefone = trim((string)($body['telefone'] ?? ''));

        if (empty($telefone)) {
            $this->badRequest('Telefone ou WhatsApp e obrigatorio.');
            return;
        }

        $bookings = $this->agendamentoModel->findFutureBookingsByPhone($arenaId, $telefone, 15);

        $this->ok([
            'total' => count($bookings),
            'agendamentos' => $bookings,
        ], 'Reservas localizadas com sucesso.');
    }

    // Check live public status of a booking and associated payment for polling
    public function getPublicStatus(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $booking = $this->agendamentoModel->findById($id);

        if (!$booking) {
            $this->notFound('Agendamento nao encontrado.');
            return;
        }

        // Get payment status
        $payment = $this->pagamentoModel->findByBookingId($id);

        $this->ok([
            'agendamento' => [
                'id' => $booking['id'],
                'arena_id' => $booking['arena_id'],
                'status' => $booking['status'],
                'data' => $booking['data'],
                'hora_inicio' => substr($booking['hora_inicio'], 0, 5),
                'hora_fim' => substr($booking['hora_fim'], 0, 5),
                'quadra_nome' => $booking['quadra_nome'] ?? 'Quadra',
                'modalidade_nome' => $booking['modalidade_nome'] ?? 'Esporte',
                'codigo_checkin' => $booking['codigo_checkin'] ?? null,
                'checkin_em' => $booking['checkin_em'] ?? null,
            ],
            'pagamento' => $payment ? [
                'id' => $payment['id'],
                'status' => $payment['status'],
                'metodo_pagamento' => $payment['metodo_pagamento'],
                'valor' => (float)$payment['valor'],
                'pago_em' => $payment['data_pagamento'] ?? null,
            ] : null,
        ], 'Status do agendamento consultado com sucesso.');
    }

    // Execute checkin via totem or portal
    public function checkin(Request $request): void
    {
        $arenaId = (int)$request->getParam('arena_id');
        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            $this->notFound('Arena esportiva nao encontrada.');
            return;
        }

        $body = $request->getBody();
        $identificador = trim((string)($body['identificador'] ?? $body['codigo'] ?? $body['telefone'] ?? ''));
        $origem = strtoupper(trim((string)($body['origem'] ?? 'TOTEM')));

        if (empty($identificador)) {
            $this->badRequest('Codigo de check-in, identificador ou telefone e obrigatorio.');
            return;
        }

        $result = $this->checkinService->processCheckin($arenaId, $identificador, $origem);

        if (!$result['success']) {
            $code = $result['code'] ?? 400;
            if ($code === 404) {
                $this->notFound($result['message']);
            } elseif ($code === 409) {
                $this->conflict($result['message']);
            } elseif ($code === 402) {
                // Payment required (HTTP 400 with detail)
                $this->badRequest($result['message']);
            } elseif ($code === 422) {
                $this->unprocessableEntity([], $result['message']);
            } else {
                $this->badRequest($result['message']);
            }
            return;
        }

        $this->ok([
            'agendamento' => $result['agendamento'],
            'detalhes' => $result['detalhes'],
        ], $result['message']);
    }
}
