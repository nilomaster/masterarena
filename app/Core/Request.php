<?php
// Master Arena SaaS - HTTP Request Handler
// Comments strictly in ASCII only.

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $queryParams;
    private array $bodyParams;
    private array $routeParams;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = $this->parsePath($this->uri);
        $this->queryParams = $_GET ?? [];
        $this->routeParams = [];
        $this->headers = $this->parseHeaders();
        $this->bodyParams = $this->parseBody();
    }

    // Parse clean path from URI without query string
    private function parsePath(string $uri): string
    {
        $parsed = parse_url($uri, PHP_URL_PATH);
        if ($parsed === false || $parsed === null) {
            $parsed = '/';
        }

        // Normalize path
        $path = '/' . trim($parsed, '/');
        return $path;
    }

    // Parse all request headers in case-insensitive manner
    private function parseHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $raw = getallheaders();
            if ($raw !== false) {
                foreach ($raw as $name => $value) {
                    $headers[strtolower($name)] = $value;
                }
                return $headers;
            }
        }

        // Fallback to $_SERVER parsing
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    // Parse JSON or form body
    private function parseBody(): array
    {
        $contentType = $this->getHeader('content-type', '');

        if (str_contains($contentType, 'application/json')) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return [];
        }

        return $_POST ?? [];
    }

    // Get HTTP method
    public function getMethod(): string
    {
        // Support method override via header or input field
        if ($this->method === 'POST') {
            $override = $this->getHeader('x-http-method-override');
            if ($override) {
                return strtoupper($override);
            }
            if (isset($this->bodyParams['_method'])) {
                return strtoupper((string)$this->bodyParams['_method']);
            }
        }

        return $this->method;
    }

    // Get normalized path
    public function getPath(): string
    {
        return $this->path;
    }

    // Get complete URI
    public function getUri(): string
    {
        return $this->uri;
    }

    // Get header value
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $lower = strtolower($name);
        return $this->headers[$lower] ?? $default;
    }

    // Extract Bearer token from Authorization header
    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('authorization');
        if (!$auth) {
            // Also check Apache authorization header fallbacks
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        }

        if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    // Set route parameters (e.g. id, slug)
    public function setParams(array $params): void
    {
        $this->routeParams = $params;
    }

    // Get specific route parameter
    public function getParam(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    // Get query parameter or all query parameters
    public function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->queryParams;
        }

        return $this->queryParams[$key] ?? $default;
    }

    // Get body input parameter or all inputs
    public function input(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->bodyParams;
        }

        return $this->bodyParams[$key] ?? $default;
    }

    // Get combined input from route, body and query
    public function all(): array
    {
        return array_merge($this->queryParams, $this->bodyParams, $this->routeParams);
    }

    // Get client IP address
    public function getIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Get client user agent
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    // Basic data validator
    public function validate(array $rules): array
    {
        $errors = [];
        $data = $this->all();

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                if ($rule === 'required') {
                    if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                        $errors[$field][] = "O campo {$field} e obrigatorio.";
                    }
                } elseif ($rule === 'email') {
                    if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "O campo {$field} deve ser um e-mail valido.";
                    }
                } elseif ($rule === 'numeric') {
                    if ($value !== null && !is_numeric($value)) {
                        $errors[$field][] = "O campo {$field} deve ser numerico.";
                    }
                } elseif (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if ($value && strlen((string)$value) < $min) {
                        $errors[$field][] = "O campo {$field} deve ter no minimo {$min} caracteres.";
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if ($value && strlen((string)$value) > $max) {
                        $errors[$field][] = "O campo {$field} deve ter no maximo {$max} caracteres.";
                    }
                }
            }
        }

        return $errors;
    }

    // Authenticated user property
    private ?array $user = null;

    // Set authenticated user context
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    // Get authenticated user context
    public function getUser(): ?array
    {
        return $this->user;
    }
}
