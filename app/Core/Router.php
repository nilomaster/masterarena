<?php
// Master Arena SaaS - RESTful Router
// Comments strictly in ASCII only.

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $currentGroupPrefix = '';
    private array $currentGroupMiddlewares = [];

    // Register GET route
    public function get(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    // Register POST route
    public function post(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    // Register PUT route
    public function put(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    // Register PATCH route
    public function patch(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    // Register DELETE route
    public function delete(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    // Register OPTIONS route
    public function options(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('OPTIONS', $path, $handler, $middlewares);
    }

    // Register a route group with prefix and optional group middlewares
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddlewares = $this->currentGroupMiddlewares;

        $this->currentGroupPrefix = rtrim($previousPrefix, '/') . '/' . trim($prefix, '/');
        $this->currentGroupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddlewares = $previousMiddlewares;
    }

    // Add route definition to registry
    private function addRoute(string $method, string $path, $handler, array $middlewares = []): self
    {
        $fullPath = rtrim($this->currentGroupPrefix, '/') . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        $mergedMiddlewares = array_merge($this->currentGroupMiddlewares, $middlewares);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $mergedMiddlewares,
            'pattern' => $this->compilePattern($fullPath),
        ];

        return $this;
    }

    // Convert route path with parameters {param} into regex pattern
    private function compilePattern(string $path): string
    {
        // Replace {param} with regex capture group
        $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    // Dispatch request through router
    public function dispatch(Request $request): void
    {
        $requestMethod = $request->getMethod();
        $requestPath = $request->getPath();

        $matchedRoute = null;
        $methodAllowed = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $requestPath, $matches)) {
                if ($route['method'] === $requestMethod) {
                    $matchedRoute = $route;

                    // Extract named route parameters
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = urldecode($value);
                        }
                    }
                    $request->setParams($params);
                    break;
                } else {
                    $methodAllowed = true;
                }
            }
        }

        // Check if route exists but method differs
        if ($matchedRoute === null && $methodAllowed) {
            Response::error('Metodo HTTP nao permitido para esta rota.', [], 405);
            return;
        }

        // Route not found
        if ($matchedRoute === null) {
            Response::notFound("Rota '{$requestPath}' nao encontrada na API.");
            return;
        }

        // Execute route middlewares pipeline
        foreach ($matchedRoute['middlewares'] as $middleware) {
            $this->executeMiddleware($middleware, $request);
        }

        // Execute controller action or closure handler
        $handler = $matchedRoute['handler'];

        if (is_callable($handler)) {
            $handler($request);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $actionMethod] = $handler;

            if (!class_exists($controllerClass)) {
                Response::serverError("Controlador '{$controllerClass}' nao encontrado.");
                return;
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $actionMethod)) {
                Response::serverError("Metodo '{$actionMethod}' nao encontrado no controlador '{$controllerClass}'.");
                return;
            }

            $controller->$actionMethod($request);
            return;
        }

        Response::serverError('Definicao de manipulador de rota invalida.');
    }

    // Execute individual middleware class or callable
    private function executeMiddleware($middleware, Request $request): void
    {
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                $instance->handle($request);
                return;
            }
        } elseif (is_callable($middleware)) {
            $middleware($request);
            return;
        }

        Response::serverError("Middleware invalido configurado na rota.");
    }
}
