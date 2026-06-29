<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

final class HttpScopePolicy
{
    /**
     * @param array<string, mixed>|null $params
     *
     * @return list<string>
     */
    public function requiredScopes(string $method, ?array $params = null): array
    {
        return match ($method) {
            'initialize',
            'notifications/initialized',
            'tools/list',
            'resources/list',
            'resources/templates/list',
            'prompts/list',
            'prompts/get',
            'completion/complete' => [HttpAuthScopes::READ],
            'logging/setLevel' => [HttpAuthScopes::ADMIN],
            'tools/call' => $this->toolCallScopes($params),
            'resources/read' => $this->resourceScopes($this->stringParam($params, 'uri')),
            default => [HttpAuthScopes::READ],
        };
    }

    /**
     * @param array<string, mixed>|null $params
     *
     * @return list<string>
     */
    public function toolCallScopes(?array $params): array
    {
        $name = $this->stringParam($params, 'name');
        $scopes = $this->toolScopes($name);

        if ($name === 'kirby_run_cli_command') {
            if ($this->boolArgument($params, 'allowWrite') === true && !in_array(HttpAuthScopes::WRITE, $scopes, true)) {
                $scopes[] = HttpAuthScopes::WRITE;
            }

            $command = $this->stringArgument($params, 'command');
            if (is_string($command) && str_starts_with(strtolower(trim($command)), 'mcp:') && !in_array(HttpAuthScopes::RUNTIME, $scopes, true)) {
                $scopes[] = HttpAuthScopes::RUNTIME;
            }
        }

        return $scopes;
    }

    /** @return list<string> */
    public function resourceScopes(?string $uri): array
    {
        if ($uri === null || $uri === '') {
            return [HttpAuthScopes::READ];
        }

        $normalized = strtolower(trim($uri));

        if (str_contains($normalized, '/update-schema')) {
            return [HttpAuthScopes::READ];
        }

        $runtimePrefixes = [
            'kirby://page/content',
            'kirby://site/content',
            'kirby://file/content',
            'kirby://user/content',
            'kirby://config/',
            'kirby://blueprint/',
        ];

        foreach ($runtimePrefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return [HttpAuthScopes::RUNTIME];
            }
        }

        return [HttpAuthScopes::READ];
    }

    /**
     * @return list<string>
     */
    public function methodScopes(string $httpMethod): array
    {
        return match (strtoupper($httpMethod)) {
            'GET' => [HttpAuthScopes::READ],
            'DELETE' => [HttpAuthScopes::ADMIN],
            default => [HttpAuthScopes::READ],
        };
    }

    /**
     * @return list<string>
     */
    public function toolScopes(?string $toolName): array
    {
        if ($toolName === null || $toolName === '') {
            return [HttpAuthScopes::READ];
        }

        if (in_array($toolName, [
            'kirby_runtime_install',
            'kirby_runtime_update',
            'kirby_cache_clear',
            'kirby_clear_cache',
            'kirby_set_log_level',
        ], true)) {
            return [HttpAuthScopes::ADMIN];
        }

        if (in_array($toolName, [
            'kirby_run_cli_command',
            'kirby_eval',
            'kirby_eval_php',
            'kirby_query_dot',
        ], true)) {
            return [HttpAuthScopes::EXECUTE];
        }

        if (
            $toolName === 'kirby_generate_ide_helpers'
            ||
            str_contains($toolName, '_update_')
            || str_starts_with($toolName, 'kirby_update_')
            || str_contains($toolName, '_create_')
            || str_contains($toolName, '_delete_')
        ) {
            return [HttpAuthScopes::WRITE];
        }

        if (in_array($toolName, [
            'kirby_read_page_content',
            'kirby_read_site_content',
            'kirby_read_file_content',
            'kirby_read_user_content',
            'kirby_routes_index',
            'kirby_dump_log_tail',
            'kirby_blueprint_read',
            'kirby_blueprints_loaded',
        ], true) || str_starts_with($toolName, 'kirby_runtime_') || str_contains($toolName, '_render_')) {
            return [HttpAuthScopes::RUNTIME];
        }

        return [HttpAuthScopes::READ];
    }

    /**
     * @param array<string, mixed>|null $params
     */
    private function stringParam(?array $params, string $name): ?string
    {
        $value = $params[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @param array<string, mixed>|null $params */
    private function boolArgument(?array $params, string $name): ?bool
    {
        $arguments = $params['arguments'] ?? null;
        if ($arguments instanceof \stdClass) {
            $arguments = (array) $arguments;
        }

        if (!is_array($arguments)) {
            return null;
        }

        $value = $arguments[$name] ?? null;

        return is_bool($value) ? $value : null;
    }

    /** @param array<string, mixed>|null $params */
    private function stringArgument(?array $params, string $name): ?string
    {
        $arguments = $params['arguments'] ?? null;
        if ($arguments instanceof \stdClass) {
            $arguments = (array) $arguments;
        }

        if (!is_array($arguments)) {
            return null;
        }

        $value = $arguments[$name] ?? null;

        return is_string($value) ? $value : null;
    }
}
