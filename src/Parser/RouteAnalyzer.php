<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

/**
 * Analyzer for Laravel routes.
 */
class RouteAnalyzer
{
    public function __construct(
        private readonly Router $router,
    ) {}

    /**
     * Get all routes that have controller actions.
     *
     * @return array<Route>
     */
    public function getControllerRoutes(): array
    {
        return array_filter(
            $this->router->getRoutes()->getRoutes(),
            fn(Route $route) => $this->hasControllerAction($route)
        );
    }

    /**
     * Get controller class and method from route.
     *
     * @return array{class: string, method: string}|null
     */
    public function getControllerAction(Route $route): ?array
    {
        $action = $route->getAction('uses');

        if (!is_string($action) || !str_contains($action, '@')) {
            return null;
        }

        [$class, $method] = explode('@', $action);

        return [
            'class' => $class,
            'method' => $method,
        ];
    }

    /**
     * Check if route has a controller action.
     */
    public function hasControllerAction(Route $route): bool
    {
        return $this->getControllerAction($route) !== null;
    }

    /**
     * Get route path in OpenAPI format.
     * Converts Laravel {param} to OpenAPI {param}.
     */
    public function getOpenApiPath(Route $route): string
    {
        $uri = $route->uri();

        // Remove leading slash
        $uri = ltrim($uri, '/');

        // Add leading slash if not empty
        if ($uri !== '') {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * Extract route parameters from route.
     *
     * @return array<string, array<string, mixed>>
     */
    public function extractParameters(Route $route): array
    {
        $parameters = [];

        // Get parameter names from URI
        preg_match_all('/\{([^}]+)\}/', $route->uri(), $matches);

        foreach ($matches[1] as $paramName) {
            $isOptional = str_ends_with($paramName, '?');
            $paramName = rtrim($paramName, '?');

            $parameters[$paramName] = [
                'name' => $paramName,
                'in' => 'path',
                'required' => !$isOptional,
                'schema' => ['type' => 'string'], // Default type
            ];

            // Try to infer type from route binding
            if ($route->hasParameter($paramName)) {
                $binding = $route->parameter($paramName);
                if (is_numeric($binding)) {
                    $parameters[$paramName]['schema']['type'] = 'integer';
                }
            }
        }

        return $parameters;
    }

    /**
     * Get HTTP methods for route.
     *
     * @return array<string>
     */
    public function getMethods(Route $route): array
    {
        return array_map(
            fn($method) => strtolower($method),
            $route->methods()
        );
    }

    /**
     * Get route middleware.
     *
     * @return array<string>
     */
    public function getMiddleware(Route $route): array
    {
        return $route->middleware();
    }

    /**
     * Check if route is protected by auth middleware.
     */
    public function isProtected(Route $route): bool
    {
        $middleware = $this->getMiddleware($route);

        foreach ($middleware as $mw) {
            if (str_contains($mw, 'auth')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get route name.
     */
    public function getName(Route $route): ?string
    {
        return $route->getName();
    }

    /**
     * Get route prefix.
     */
    public function getPrefix(Route $route): ?string
    {
        return $route->getPrefix();
    }

    /**
     * Group routes by controller.
     *
     * @return array<string, array<Route>>
     */
    public function groupByController(): array
    {
        $grouped = [];

        foreach ($this->getControllerRoutes() as $route) {
            $action = $this->getControllerAction($route);

            if ($action === null) {
                continue;
            }

            $controller = $action['class'];

            if (!isset($grouped[$controller])) {
                $grouped[$controller] = [];
            }

            $grouped[$controller][] = $route;
        }

        return $grouped;
    }

    /**
     * Get unique controller classes from routes.
     *
     * @return array<string>
     */
    public function getControllerClasses(): array
    {
        $controllers = [];

        foreach ($this->getControllerRoutes() as $route) {
            $action = $this->getControllerAction($route);

            if ($action !== null) {
                $controllers[] = $action['class'];
            }
        }

        return array_unique($controllers);
    }
}
