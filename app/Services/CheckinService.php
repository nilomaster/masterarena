<?php
// Master Arena SaaS - Checkin Domain Service
// Comments strictly in ASCII only.

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Arena;
use Database\Connection;
use PDO;

class CheckinService
{
    private PDO $pdo;
    private Agendamento $agendamentoModel;
    private Arena $arenaModel;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->agendamentoModel = new Agendamento();
        $this->arenaModel = new Arena();
    }

    // Process checkin for an appointment by checkin code or booking ID
    public function processCheckin(int $arenaId, string $identifier, string $origem = 'TOTEM'): array
    {
        $identifier = trim($identifier);
        if (empty($identifier)) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Codigo de check-in ou identificador e obrigatorio.',
            ];
        }

        $arena = $this->arenaModel->findById($arenaId);
        if (!$arena) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Arena esportiva nao encontrada.',
            ];
        }

        // 1. Locate booking
        $booking = $this->agendamentoModel->findByCheckinCode($arenaId, $identifier);

        // Fallback: If not found and identifier is numeric or phone, search by phone
        if (!$booking && preg_match('/^\d{8,14}$/', preg_replace('/\D/', '', $identifier))) {
            $todayBookings = $this->agendamentoModel->findTodayBookingsByPhone($arenaId, $identifier);
            if (!empty($todayBookings)) {
                // Pick the first available confirmed or pending booking of today
                $booking = $todayBookings[0];
            }
        }

        if (!$booking) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Nenhum agendamento correspondente foi localizado para esta arena.',
            ];
        }

        // 2. Validate date of booking (Must be today)
        $today = date('Y-m-d');
        if ($booking['data'] !== $today) {
            $bookingDateFormatted = date('d/m/Y', strtotime($booking['data']));
            if ($booking['data'] > $today) {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => "Este agendamento esta marcado para o dia {$bookingDateFormatted}. O check-in so e liberado no dia do jogo.",
                    'agendamento' => $booking,
                ];
            } else {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => "Este agendamento pertencia ao dia {$bookingDateFormatted} e ja encerrou sua validade.",
                    'agendamento' => $booking,
                ];
            }
        }

        // 3. Validate status
        if ($booking['status'] === 'CANCELADO') {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Este agendamento foi cancelado e nao permite check-in.',
                'agendamento' => $booking,
            ];
        }

        if ($booking['status'] === 'PENDENTE') {
            return [
                'success' => false,
                'code' => 402,
                'message' => 'O pagamento desta reserva ainda esta pendente. Efetue o pagamento via PIX para liberar o check-in.',
                'requer_pagamento' => true,
                'agendamento' => $booking,
            ];
        }

        // 4. Validate if checkin was already performed
        if (!empty($booking['checkin_em'])) {
            $checkinTimeFormatted = date('H:i', strtotime($booking['checkin_em']));
            return [
                'success' => false,
                'code' => 409,
                'message' => "Check-in ja foi realizado as {$checkinTimeFormatted} ({$booking['checkin_origem']}). Entrada ja liberada.",
                'agendamento' => $booking,
                'ja_realizado' => true,
            ];
        }

        // 5. Complete checkin transaction
        $marked = $this->agendamentoModel->markCheckin((int)$booking['id'], $origem);
        if (!$marked) {
            return [
                'success' => false,
                'code' => 500,
                'message' => 'Falha ao gravar check-in no sistema.',
            ];
        }

        // Record history
        $this->agendamentoModel->recordHistory(
            (int)$booking['id'],
            $arenaId,
            null,
            'CHECKIN',
            $booking['status'],
            $booking['status'],
            'Check-in realizado com sucesso via ' . $origem
        );

        // Fetch refreshed booking
        $refreshed = $this->agendamentoModel->findById((int)$booking['id']);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Check-in realizado com sucesso! Entrada liberada para a quadra.',
            'agendamento' => $refreshed,
            'detalhes' => [
                'cliente' => $booking['cliente_nome'] ?? 'Cliente',
                'quadra' => $booking['quadra_nome'] ?? 'Quadra Esportiva',
                'modalidade' => $booking['modalidade_nome'] ?? 'Esporte',
                'horario' => substr($booking['hora_inicio'], 0, 5) . ' as ' . substr($booking['hora_fim'], 0, 5),
                'checkin_em' => date('d/m/Y H:i:s'),
                'origem' => $origem,
            ],
        ];
    }
}
