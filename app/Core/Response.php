<?php
// Master Arena SaaS - HTTP Response Handler
// Comments strictly in ASCII only.

namespace App\Core;

class Response
{
    // Send standard JSON response and exit
    public static function json(array $data, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            foreach ($headers as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (defined('MASTER_ARENA_TEST_MODE')) {
            throw new EarlyExitException($statusCode, $data);
        }

        exit;
    }

    // Standard API success response format
    public static function success($data = null, string $message = 'Operacao realizada com sucesso.', int $statusCode = 200): void
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data ?? (object)[],
        ];

        self::json($payload, $statusCode);
    }

    // Standard 201 Created response
    public static function created($data = null, string $message = 'Registro criado com sucesso.'): void
    {
        self::success($data, $message, 201);
    }

    // Standard API error response format
    public static function error(string $message = 'Ocorreu um erro no processamento.', array $errors = [], int $statusCode = 400): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];

        self::json($payload, $statusCode);
    }

    // HTTP 400 Bad Request
    public static function badRequest(string $message = 'Requisicao invalida.', array $errors = []): void
    {
        self::error($message, $errors, 400);
    }

    // HTTP 401 Unauthorized
    public static function unauthorized(string $message = 'Acesso nao autorizado. Token ausente ou expirado.'): void
    {
        self::error($message, [], 401);
    }

    // HTTP 403 Forbidden
    public static function forbidden(string $message = 'Voce nao tem permissao para acessar este recurso.'): void
    {
        self::error($message, [], 403);
    }

    // HTTP 404 Not Found
    public static function notFound(string $message = 'Recurso nao encontrado.'): void
    {
        self::error($message, [], 404);
    }

    // HTTP 409 Conflict
    public static function conflict(string $message = 'Conflito de agendamento ou dados duplicados.'): void
    {
        self::error($message, [], 409);
    }

    // HTTP 422 Unprocessable Entity (Validation error)
    public static function validationError(array $errors, string $message = 'Dados fornecidos invalidos.'): void
    {
        self::error($message, $errors, 422);
    }

    // HTTP 500 Internal Server Error
    public static function serverError(string $message = 'Erro interno do servidor.', ?string $debug = null): void
    {
        $errors = [];
        $appConfig = require __DIR__ . '/../../config/app.php';

        if (!empty($appConfig['debug']) && $debug !== null) {
            $errors['debug'] = $debug;
        }

        self::error($message, $errors, 500);
    }
}
