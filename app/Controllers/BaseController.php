<?php
// Master Arena SaaS - Base Controller
// Comments strictly in ASCII only.

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

abstract class BaseController
{
    // Return standard success response
    protected function success($data = null, string $message = 'Operacao realizada com sucesso.', int $statusCode = 200): void
    {
        Response::success($data, $message, $statusCode);
    }

    // Return standard created response
    protected function created($data = null, string $message = 'Registro criado com sucesso.'): void
    {
        Response::created($data, $message);
    }

    // Return standard error response
    protected function error(string $message = 'Ocorreu um erro.', array $errors = [], int $statusCode = 400): void
    {
        Response::error($message, $errors, $statusCode);
    }

    // Return bad request error
    protected function badRequest(string $message = 'Requisicao invalida.', array $errors = []): void
    {
        Response::badRequest($message, $errors);
    }

    // Return unauthorized error
    protected function unauthorized(string $message = 'Acesso nao autorizado.'): void
    {
        Response::unauthorized($message);
    }

    // Return forbidden error
    protected function forbidden(string $message = 'Acesso proibido.'): void
    {
        Response::forbidden($message);
    }

    // Return not found error
    protected function notFound(string $message = 'Recurso nao encontrado.'): void
    {
        Response::notFound($message);
    }

    // Return conflict error
    protected function conflict(string $message = 'Conflito de dados.'): void
    {
        Response::conflict($message);
    }

    // Return validation error response
    protected function validationError(array $errors, string $message = 'Dados invalidos fornecidos.'): void
    {
        Response::validationError($errors, $message);
    }

    // Return unprocessable entity error response
    protected function unprocessableEntity(array $errors = [], string $message = 'Entidade nao processavel.'): void
    {
        Response::validationError($errors, $message);
    }

    // Validate request inputs and respond with 422 if validation fails
    protected function validate(Request $request, array $rules): void
    {
        $errors = $request->validate($rules);
        if (!empty($errors)) {
            $this->validationError($errors);
        }
    }
}
