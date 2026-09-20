<?php
// Master Arena SaaS - WhatsApp Messaging & Notifications Core Service
// Comments strictly in ASCII only.

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Arena;
use App\Models\Cliente;
use App\Models\NotificacaoWhatsApp;
use App\Models\Pagamento;
use Database\Connection;
use PDO;
use Throwable;

class WhatsAppService
{
    private PDO $pdo;
    private Arena $arenaModel;
    private Agendamento $agendamentoModel;
    private Cliente $clienteModel;
    private Pagamento $pagamentoModel;
    private NotificacaoWhatsApp $notificacaoModel;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->arenaModel = new Arena($this->pdo);
        $this->agendamentoModel = new Agendamento($this->pdo);
        $this->clienteModel = new Cliente($this->pdo);
        $this->pagamentoModel = new Pagamento($this->pdo);
        $this->notificacaoModel = new NotificacaoWhatsApp($this->pdo);
    }

    // Clean and normalize Brazilian phone numbers to international standard 55DDD9XXXXXXXX
    public function formatPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // If phone has 10 or 11 digits (without DDI), prepend 55
        if (strlen($clean) === 10 || strlen($clean) === 11) {
            return '55' . $clean;
        }

        // If already starts with 55 and has 12 or 13 digits
        if (str_starts_with($clean, '55') && (strlen($clean) === 12 || strlen($clean) === 13)) {
            return $clean;
        }

        return $clean;
    }

    // Dispatch raw text message through configured gateway provider
    public function sendMessage(
        int $arenaId,
        string $phone,
        string $message,
        string $tipo = 'TESTE',
        ?int $clienteId = null,
        ?int $agendamentoId = null
    ): array {
        $settings = $this->arenaModel->getSettings($arenaId);

        $enabled = ($settings['whatsapp_enabled'] ?? '1') === '1';
        $provider = strtoupper(trim((string)($settings['whatsapp_provider'] ?? 'SIMULATOR')));
        $apiUrl = trim((string)($settings['whatsapp_api_url'] ?? ''));
        $apiToken = trim((string)($settings['whatsapp_api_token'] ?? ''));
        $instance = trim((string)($settings['whatsapp_instance'] ?? ''));

        $formattedPhone = $this->formatPhone($phone);

        // 1. Create notification record with PENDENTE status
        $notifId = $this->notificacaoModel->create([
            'arena_id' => $arenaId,
            'cliente_id' => $clienteId,
            'agendamento_id' => $agendamentoId,
            'tipo' => $tipo,
            'telefone' => $formattedPhone,
            'mensagem' => $message,
            'status' => 'PENDENTE',
            'gateway_provider' => $provider,
        ]);

        if (!$enabled && $provider !== 'SIMULATOR') {
            $msg = 'Envios de WhatsApp desativados nas configuracoes da arena.';
            $this->notificacaoModel->markAsFailed($notifId, $msg);
            return [
                'success' => false,
                'code' => 403,
                'message' => $msg,
                'notification_id' => $notifId,
            ];
        }

        // 2. Dispatch message according to selected driver
        try {
            if ($provider === 'SIMULATOR') {
                $mockMsgId = 'sim_' . bin2hex(random_bytes(8));
                $responsePayload = json_encode([
                    'status' => 'success',
                    'driver' => 'SIMULATOR',
                    'dispatched_at' => date('Y-m-d H:i:s'),
                    'mock_message_id' => $mockMsgId,
                    'recipient' => $formattedPhone,
                ]);

                $this->notificacaoModel->markAsSent($notifId, $responsePayload);

                return [
                    'success' => true,
                    'status' => 'ENVIADO',
                    'message' => 'Mensagem simulada e registrada com sucesso.',
                    'notification_id' => $notifId,
                    'provider' => 'SIMULATOR',
                    'external_message_id' => $mockMsgId,
                ];
            } elseif ($provider === 'EVOLUTION_API') {
                $endpoint = rtrim($apiUrl, '/') . '/message/sendText/' . urlencode($instance);
                $body = json_encode([
                    'number' => $formattedPhone,
                    'text' => $message,
                ]);

                $ch = curl_init($endpoint);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'apikey: ' . $apiToken,
                    ],
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);

                if ($err || ($httpCode !== 200 && $httpCode !== 201)) {
                    $errorMsg = $err ?: "Evolution API returned HTTP {$httpCode}: {$response}";
                    $this->notificacaoModel->markAsFailed($notifId, $errorMsg);
                    return [
                        'success' => false,
                        'message' => $errorMsg,
                        'notification_id' => $notifId,
                    ];
                }

                $this->notificacaoModel->markAsSent($notifId, (string)$response);
                return [
                    'success' => true,
                    'message' => 'Mensagem enviada com sucesso via Evolution API.',
                    'notification_id' => $notifId,
                    'provider' => 'EVOLUTION_API',
                ];
            } else {
                // Generic / mock fallback for unconfigured providers
                $mockResp = json_encode(['driver' => $provider, 'status' => 'simulated']);
                $this->notificacaoModel->markAsSent($notifId, $mockResp);
                return [
                    'success' => true,
                    'message' => "Mensagem processada pelo driver {$provider}.",
                    'notification_id' => $notifId,
                    'provider' => $provider,
                ];
            }
        } catch (Throwable $e) {
            $this->notificacaoModel->markAsFailed($notifId, $e->getMessage());
            return [
                'success' => false,
                'message' => 'Falha no gateway WhatsApp: ' . $e->getMessage(),
                'notification_id' => $notifId,
            ];
        }
    }

    // Interpolate message template placeholders with live booking and arena data
    public function renderTemplate(string $template, array $data): string
    {
        $placeholders = [
            '{cliente_nome}' => $data['cliente_nome'] ?? 'Cliente',
            '{arena_nome}' => $data['arena_nome'] ?? 'Master Arena',
            '{quadra_nome}' => $data['quadra_nome'] ?? 'Quadra Esportiva',
            '{modalidade_nome}' => $data['modalidade_nome'] ?? 'Esporte',
            '{data}' => !empty($data['data']) ? date('d/m/Y', strtotime($data['data'])) : date('d/m/Y'),
            '{horario}' => ($data['hora_inicio'] ?? '00:00') . ' as ' . ($data['hora_fim'] ?? '00:00'),
            '{hora_inicio}' => $data['hora_inicio'] ?? '00:00',
            '{hora_fim}' => $data['hora_fim'] ?? '00:00',
            '{valor}' => isset($data['valor']) ? 'R$ ' . number_format((float)$data['valor'], 2, ',', '.') : 'R$ 0,00',
            '{pix_copia_cola}' => $data['pix_copia_cola'] ?? '',
            '{codigo_checkin}' => $data['codigo_checkin'] ?? 'CHK-0000',
            '{motivo}' => $data['motivo'] ?? 'Nao especificado',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    // 1. Dispatch PIX billing notification upon booking creation
    public function sendPixPending(int $bookingId, ?string $pixCopiaCola = null): array
    {
        $booking = $this->agendamentoModel->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Agendamento nao encontrado.'];
        }

        $arenaId = (int)$booking['arena_id'];
        $arena = $this->arenaModel->findById($arenaId);
        $settings = $this->arenaModel->getSettings($arenaId);

        if (($settings['whatsapp_notify_pix'] ?? '1') !== '1') {
            return ['success' => false, 'message' => 'Notificacao de PIX desativada.'];
        }

        // Get PIX payload if not provided
        if (!$pixCopiaCola) {
            $payment = $this->pagamentoModel->findByAgendamentoId($bookingId);
            $pixCopiaCola = $payment['pix_copia_cola'] ?? '';
        }

        $template = "Ola, *{cliente_nome}*! Sua reserva na *{arena_nome}* foi gerada com sucesso.\n\n" .
            "📅 *Data:* {data}\n" .
            "⏰ *Horario:* {horario}\n" .
            "🏟️ *Quadra:* {quadra_nome} ({modalidade_nome})\n" .
            "💰 *Valor:* {valor}\n\n" .
            "Para confirmar seu horario, pague usando o PIX Copia e Cola abaixo:\n\n" .
            "{pix_copia_cola}\n\n" .
            "A liberacao ocorre automaticamente em instantes apos o pagamento!";

        $text = $this->renderTemplate($template, [
            'cliente_nome' => $booking['cliente_nome'],
            'arena_nome' => $arena['nome_arena'] ?? 'Arena',
            'quadra_nome' => $booking['quadra_nome'] ?? 'Quadra',
            'modalidade_nome' => $booking['modalidade_nome'] ?? 'Esporte',
            'data' => $booking['data'],
            'hora_inicio' => substr($booking['hora_inicio'], 0, 5),
            'hora_fim' => substr($booking['hora_fim'], 0, 5),
            'valor' => $booking['valor_final'],
            'pix_copia_cola' => $pixCopiaCola,
        ]);

        return $this->sendMessage(
            $arenaId,
            $booking['cliente_whatsapp'],
            $text,
            'PIX_PENDENTE',
            (int)$booking['cliente_id'],
            $bookingId
        );
    }

    // 2. Dispatch booking confirmation notification with checkin code
    public function sendBookingConfirmed(int $bookingId): array
    {
        $booking = $this->agendamentoModel->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Agendamento nao encontrado.'];
        }

        $arenaId = (int)$booking['arena_id'];
        $arena = $this->arenaModel->findById($arenaId);
        $settings = $this->arenaModel->getSettings($arenaId);

        if (($settings['whatsapp_notify_confirmed'] ?? '1') !== '1') {
            return ['success' => false, 'message' => 'Notificacao de confirmacao desativada.'];
        }

        $template = "🎉 *Reserva Confirmada!*\n\n" .
            "Ola, *{cliente_nome}*! Seu pagamento foi aprovado e a quadra esta garantida na *{arena_nome}*.\n\n" .
            "📅 *Data:* {data}\n" .
            "⏰ *Horario:* {horario}\n" .
            "🏟️ *Quadra:* {quadra_nome} ({modalidade_nome})\n\n" .
            "⚡ *Seu Codigo de Check-in:* *{codigo_checkin}*\n\n" .
            "Ao chegar na arena, digite este codigo no totem de autoatendimento para liberar a quadra. Bom jogo!";

        $text = $this->renderTemplate($template, [
            'cliente_nome' => $booking['cliente_nome'],
            'arena_nome' => $arena['nome_arena'] ?? 'Arena',
            'quadra_nome' => $booking['quadra_nome'] ?? 'Quadra',
            'modalidade_nome' => $booking['modalidade_nome'] ?? 'Esporte',
            'data' => $booking['data'],
            'hora_inicio' => substr($booking['hora_inicio'], 0, 5),
            'hora_fim' => substr($booking['hora_fim'], 0, 5),
            'codigo_checkin' => $booking['codigo_checkin'] ?? ('CHK-' . $booking['id']),
        ]);

        return $this->sendMessage(
            $arenaId,
            $booking['cliente_whatsapp'],
            $text,
            'RESERVA_CONFIRMADA',
            (int)$booking['cliente_id'],
            $bookingId
        );
    }

    // 3. Dispatch booking cancellation notification
    public function sendBookingCancelled(int $bookingId, ?string $motivo = null): array
    {
        $booking = $this->agendamentoModel->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Agendamento nao encontrado.'];
        }

        $arenaId = (int)$booking['arena_id'];
        $arena = $this->arenaModel->findById($arenaId);
        $settings = $this->arenaModel->getSettings($arenaId);

        if (($settings['whatsapp_notify_cancelled'] ?? '1') !== '1') {
            return ['success' => false, 'message' => 'Notificacao de cancelamento desativada.'];
        }

        $template = "⚠️ *Aviso de Cancelamento de Reserva*\n\n" .
            "Ola, *{cliente_nome}*! Sua reserva na *{arena_nome}* para {data} as {horario} foi cancelada.\n\n" .
            "🏟️ *Quadra:* {quadra_nome}\n" .
            "📝 *Motivo:* {motivo}\n\n" .
            "Duvidas? Entre em contato com a nossa recepcao.";

        $text = $this->renderTemplate($template, [
            'cliente_nome' => $booking['cliente_nome'],
            'arena_nome' => $arena['nome_arena'] ?? 'Arena',
            'quadra_nome' => $booking['quadra_nome'] ?? 'Quadra',
            'data' => $booking['data'],
            'hora_inicio' => substr($booking['hora_inicio'], 0, 5),
            'hora_fim' => substr($booking['hora_fim'], 0, 5),
            'motivo' => $motivo ?: 'Cancelamento solicitado',
        ]);

        return $this->sendMessage(
            $arenaId,
            $booking['cliente_whatsapp'],
            $text,
            'CANCELAMENTO',
            (int)$booking['cliente_id'],
            $bookingId
        );
    }

    // 4. Dispatch pre-game reminder notification
    public function sendGameReminder(int $bookingId): array
    {
        // Prevent duplicate reminder
        if ($this->notificacaoModel->hasReminderBeenSent($bookingId)) {
            return [
                'success' => false,
                'message' => 'Lembrete de jogo ja foi enviado anteriormente para esta reserva.',
            ];
        }

        $booking = $this->agendamentoModel->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Agendamento nao encontrado.'];
        }

        $arenaId = (int)$booking['arena_id'];
        $arena = $this->arenaModel->findById($arenaId);

        $template = "⏰ *Lembrete de Partida!*\n\n" .
            "Ola, *{cliente_nome}*! Sua partida na *{arena_nome}* comeca em breve.\n\n" .
            "🏟️ *Quadra:* {quadra_nome} ({modalidade_nome})\n" .
            "⏰ *Horario:* {horario}\n" .
            "⚡ *Codigo de Check-in:* *{codigo_checkin}*\n\n" .
            "Chegue com alguns minutos de antecedencia para liberar a entrada no totem. Bom esporte!";

        $text = $this->renderTemplate($template, [
            'cliente_nome' => $booking['cliente_nome'],
            'arena_nome' => $arena['nome_arena'] ?? 'Arena',
            'quadra_nome' => $booking['quadra_nome'] ?? 'Quadra',
            'modalidade_nome' => $booking['modalidade_nome'] ?? 'Esporte',
            'data' => $booking['data'],
            'hora_inicio' => substr($booking['hora_inicio'], 0, 5),
            'hora_fim' => substr($booking['hora_fim'], 0, 5),
            'codigo_checkin' => $booking['codigo_checkin'] ?? ('CHK-' . $booking['id']),
        ]);

        return $this->sendMessage(
            $arenaId,
            $booking['cliente_whatsapp'],
            $text,
            'LEMBRETE_JOGO',
            (int)$booking['cliente_id'],
            $bookingId
        );
    }

    // 5. Batch process pending reminders for today's upcoming confirmed matches
    public function processPendingReminders(int $arenaId): array
    {
        $settings = $this->arenaModel->getSettings($arenaId);
        if (($settings['whatsapp_notify_reminder'] ?? '1') !== '1') {
            return [
                'success' => true,
                'message' => 'Envio de lembretes desativado nas configuracoes.',
                'processed' => 0,
            ];
        }

        $today = date('Y-m-d');
        $now = date('H:i:s');

        // Look for today's confirmed bookings starting after current time
        $stmt = $this->pdo->prepare("SELECT id FROM `agendamentos` 
            WHERE `arena_id` = :arena_id 
              AND `data` = :data 
              AND `status` = 'CONFIRMADO' 
              AND `hora_inicio` >= :now 
            ORDER BY `hora_inicio` ASC");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':data' => $today,
            ':now' => $now,
        ]);

        $bookingIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $dispatched = 0;

        foreach ($bookingIds as $bId) {
            $id = (int)$bId;
            if (!$this->notificacaoModel->hasReminderBeenSent($id)) {
                $res = $this->sendGameReminder($id);
                if ($res['success']) {
                    $dispatched++;
                }
            }
        }

        return [
            'success' => true,
            'message' => "Processamento concluido: {$dispatched} lembrete(s) disparado(s).",
            'dispatched_count' => $dispatched,
            'total_inspected' => count($bookingIds),
        ];
    }

    // 6. Send test message for administrator validation
    public function sendTestMessage(int $arenaId, string $phone): array
    {
        $arena = $this->arenaModel->findById($arenaId);
        $arenaNome = $arena['nome_arena'] ?? 'Master Arena';

        $settings = $this->arenaModel->getSettings($arenaId);
        $provider = $settings['whatsapp_provider'] ?? 'SIMULATOR';

        $msg = " Master Arena SaaS — Teste de Integracao com Sucesso!\n\n" .
            "Arena: *{$arenaNome}*\n" .
            "Driver Ativo: *{$provider}*\n" .
            "Data/Hora: " . date('d/m/Y H:i:s') . "\n\n" .
            "Sua conexao de WhatsApp esta pronta para enviar comprovantes, PIX e lembretes!";

        return $this->sendMessage(
            $arenaId,
            $phone,
            $msg,
            'TESTE'
        );
    }
}
