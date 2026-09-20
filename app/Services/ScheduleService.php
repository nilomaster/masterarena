<?php
// Master Arena SaaS - Schedule & Availability Core Service
// Comments strictly in ASCII only.

namespace App\Services;

use App\Models\Arena;
use App\Models\Bloqueio;
use App\Models\Horario;
use App\Models\Quadra;
use App\Models\ValorHorario;
use Database\Connection;
use DateTime;
use PDO;

class ScheduleService
{
    private PDO $pdo;
    private Arena $arenaModel;
    private Quadra $quadraModel;
    private Horario $horarioModel;
    private Bloqueio $bloqueioModel;
    private ValorHorario $valorHorarioModel;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->arenaModel = new Arena($this->pdo);
        $this->quadraModel = new Quadra($this->pdo);
        $this->horarioModel = new Horario($this->pdo);
        $this->bloqueioModel = new Bloqueio($this->pdo);
        $this->valorHorarioModel = new ValorHorario($this->pdo);
    }

    // Generate comprehensive court availability grid for a specific date
    public function getAvailabilityGrid(
        int $arenaId,
        string $date,
        ?int $filterQuadraId = null,
        ?int $filterModalidadeId = null,
        bool $isPublicView = false
    ): array {
        // Validate date format YYYY-MM-DD
        $dt = DateTime::createFromFormat('Y-m-d', trim($date));
        if (!$dt || $dt->format('Y-m-d') !== trim($date)) {
            $dt = new DateTime();
            $date = $dt->format('Y-m-d');
        }

        $dayOfWeek = (int)$dt->format('w'); // 0=Sun, 1=Mon, ..., 6=Sat
        $dayNames = ['Domingo', 'Segunda-feira', 'Terca-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado'];

        // Get operating hours for the day
        $daySchedule = $this->horarioModel->getScheduleForDay($arenaId, $dayOfWeek);
        $settings = $this->arenaModel->getSettings($arenaId);

        $horaAbertura = $daySchedule['hora_inicio'] ?? ($settings['horario_abertura'] ?? '06:00');
        $horaFechamento = $daySchedule['hora_fim'] ?? ($settings['horario_fechamento'] ?? '23:00');
        $duracaoMinutos = isset($daySchedule['duracao_minutos']) ? (int)$daySchedule['duracao_minutos'] : 60;
        $intervaloMinutos = isset($daySchedule['intervalo_minutos']) ? (int)$daySchedule['intervalo_minutos'] : 0;
        $isOpen = $daySchedule ? (bool)$daySchedule['ativo'] : true;

        // Fetch arena courts
        $courtStatusFilter = $isPublicView ? 'ATIVO' : null;
        $allCourts = $this->quadraModel->listByArena($arenaId, $filterModalidadeId, $courtStatusFilter);

        // Filter single court if requested
        if ($filterQuadraId !== null) {
            $allCourts = array_filter($allCourts, function ($c) use ($filterQuadraId) {
                return (int)$c['id'] === $filterQuadraId;
            });
            $allCourts = array_values($allCourts);
        }

        // Generate day time slots
        $baseSlots = [];
        if ($isOpen) {
            $baseSlots = $this->generateDaySlots($horaAbertura, $horaFechamento, $duracaoMinutos, $intervaloMinutos);
        }

        // Fetch bookings for this arena and date (excluding canceled)
        $stmtBookings = $this->pdo->prepare("SELECT id, quadra_id, hora_inicio, hora_fim, status, cliente_id 
            FROM `agendamentos` 
            WHERE `arena_id` = :arena_id AND `data` = :data AND `status` != 'CANCELADO'");
        $stmtBookings->execute([':arena_id' => $arenaId, ':data' => $date]);
        $existingBookings = $stmtBookings->fetchAll() ?: [];

        // Fetch blocks overlapping this date
        $existingBlocks = $this->bloqueioModel->listByArena($arenaId, null, $date, $date);

        $gridCourts = [];

        foreach ($allCourts as $court) {
            $courtId = (int)$court['id'];
            $modalidadeId = (int)$court['modalidade_id'];
            $defaultPrice = (float)$court['valor_padrao'];
            $courtStatus = $court['status']; // ATIVO, MANUTENCAO, INATIVO

            $courtSlots = [];

            foreach ($baseSlots as $slot) {
                $slotStart = $slot['hora_inicio'];
                $slotEnd = $slot['hora_fim'];

                // 1. Check if court is in maintenance or inactive
                if ($courtStatus === 'MANUTENCAO') {
                    $courtSlots[] = [
                        'hora_inicio' => $slotStart,
                        'hora_fim' => $slotEnd,
                        'status' => 'MANUTENCAO',
                        'motivo' => 'Quadra em manutencao operacional',
                        'valor' => $defaultPrice,
                    ];
                    continue;
                }

                // 2. Check blocks
                $matchedBlock = null;
                foreach ($existingBlocks as $block) {
                    if ((int)$block['quadra_id'] === $courtId) {
                        // Check time overlap: blockStart < slotEnd AND blockEnd > slotStart
                        if ($block['hora_inicio'] < $slotEnd && $block['hora_fim'] > $slotStart) {
                            $matchedBlock = $block;
                            break;
                        }
                    }
                }

                if ($matchedBlock) {
                    $courtSlots[] = [
                        'hora_inicio' => $slotStart,
                        'hora_fim' => $slotEnd,
                        'status' => 'BLOQUEADO',
                        'bloqueio_id' => (int)$matchedBlock['id'],
                        'motivo' => $matchedBlock['motivo'],
                        'valor' => $defaultPrice,
                    ];
                    continue;
                }

                // 3. Check existing bookings
                $matchedBooking = null;
                foreach ($existingBookings as $booking) {
                    if ((int)$booking['quadra_id'] === $courtId) {
                        if ($booking['hora_inicio'] < $slotEnd && $booking['hora_fim'] > $slotStart) {
                            $matchedBooking = $booking;
                            break;
                        }
                    }
                }

                if ($matchedBooking) {
                    $courtSlots[] = [
                        'hora_inicio' => $slotStart,
                        'hora_fim' => $slotEnd,
                        'status' => 'RESERVADO',
                        'agendamento_id' => (int)$matchedBooking['id'],
                        'booking_status' => $matchedBooking['status'],
                        'valor' => $defaultPrice,
                    ];
                    continue;
                }

                // 4. Slot is available: resolve dynamic price
                $resolvedPrice = $this->valorHorarioModel->resolvePrice(
                    $arenaId,
                    $courtId,
                    $modalidadeId,
                    $dayOfWeek,
                    $slotStart,
                    $slotEnd,
                    $defaultPrice
                );

                $courtSlots[] = [
                    'hora_inicio' => $slotStart,
                    'hora_fim' => $slotEnd,
                    'status' => 'LIVRE',
                    'valor' => $resolvedPrice,
                ];
            }

            $gridCourts[] = [
                'id' => $courtId,
                'nome' => $court['nome'],
                'modalidade_id' => $modalidadeId,
                'modalidade_nome' => $court['modalidade_nome'] ?? 'Padrao',
                'capacidade' => (int)$court['capacidade'],
                'valor_padrao' => $defaultPrice,
                'status' => $courtStatus,
                'slots' => $courtSlots,
            ];
        }

        return [
            'data' => $date,
            'dia_semana' => $dayOfWeek,
            'dia_semana_nome' => $dayNames[$dayOfWeek] ?? 'Desconhecido',
            'aberto' => $isOpen,
            'horario_funcionamento' => [
                'hora_inicio' => substr($horaAbertura, 0, 5),
                'hora_fim' => substr($horaFechamento, 0, 5),
                'duracao_minutos' => $duracaoMinutos,
                'intervalo_minutos' => $intervaloMinutos,
            ],
            'total_quadras' => count($gridCourts),
            'quadras' => $gridCourts,
        ];
    }

    // Generate chronological time slots for a day
    public function generateDaySlots(
        string $horaAbertura,
        string $horaFechamento,
        int $duracaoMinutos = 60,
        int $intervaloMinutos = 0
    ): array {
        $slots = [];

        $startMinutes = $this->timeToMinutes($horaAbertura);
        $endMinutes = $this->timeToMinutes($horaFechamento);

        if ($endMinutes <= $startMinutes) {
            // Handle cross-midnight or 24h: default to 23:59
            $endMinutes = 1440;
        }

        $step = max(15, $duracaoMinutos + $intervaloMinutos);
        $current = $startMinutes;

        while ($current + $duracaoMinutos <= $endMinutes) {
            $slotStart = $this->minutesToTime($current);
            $slotEnd = $this->minutesToTime($current + $duracaoMinutos);

            $slots[] = [
                'hora_inicio' => $slotStart,
                'hora_fim' => $slotEnd,
            ];

            $current += $step;
        }

        return $slots;
    }

    // Helper: Convert TIME string "HH:MM" to total minutes from midnight
    private function timeToMinutes(string $timeStr): int
    {
        $parts = explode(':', trim($timeStr));
        $hours = isset($parts[0]) ? (int)$parts[0] : 0;
        $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
        return ($hours * 60) + $minutes;
    }

    // Helper: Convert total minutes from midnight to "HH:MM:SS"
    private function minutesToTime(int $totalMinutes): string
    {
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d:00', $hours, $minutes);
    }
}
