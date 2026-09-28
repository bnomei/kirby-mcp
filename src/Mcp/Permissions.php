<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Capability\Registry;

final readonly class Permissions
{
    public const CATEGORY = 'bnomei.kirby-mcp';

    public const TOOLS = [
        'kirby_tool_suggest' => 'discovery',
        'kirby_read_page_content' => 'content.pages.read',
        'kirby_update_page_content' => 'content.pages.update',
        'kirby_render_page' => 'content.pages.render',
        'kirby_read_file_content' => 'content.files.read',
        'kirby_update_file_content' => 'content.files.update',
        'kirby_read_site_content' => 'content.site.read',
        'kirby_update_site_content' => 'content.site.update',
        'kirby_read_user_content' => 'content.users.read',
        'kirby_update_user_content' => 'content.users.update',
        'kirby_init' => 'project.info',
        'kirby_info' => 'project.info',
        'kirby_roots' => 'project.roots',
        'kirby_blueprints_index' => 'project.blueprints',
        'kirby_blueprints_loaded' => 'project.blueprints',
        'kirby_blueprint_read' => 'project.blueprints',
        'kirby_templates_index' => 'project.templates',
        'kirby_snippets_index' => 'project.snippets',
        'kirby_collections_index' => 'project.collections',
        'kirby_controllers_index' => 'project.controllers',
        'kirby_models_index' => 'project.models',
        'kirby_plugins_index' => 'project.plugins',
        'kirby_routes_index' => 'project.routes',
        'kirby_composer_audit' => 'project.composer',
        'kirby_cli_version' => 'project.cli',
        'kirby_search' => 'reference.knowledge',
        'kirby_online' => 'reference.online',
        'kirby_online_plugins' => 'reference.online',
        'kirby_dump_log_tail' => 'diagnostics.dumps',
        'kirby_query_dot' => 'execution.query',
        'kirby_eval' => 'execution.php',
        'kirby_run_cli_command' => 'execution.cli',
        'kirby_runtime_status' => 'maintenance.runtime.status',
        'kirby_runtime_install' => 'maintenance.runtime.install',
        'kirby_cache_clear' => 'maintenance.cache.clear',
        'kirby_ide_helpers_status' => 'maintenance.ide.status',
        'kirby_generate_ide_helpers' => 'maintenance.ide.generate',
    ];

    public const RESOURCES = [
        'kirby://tools' => 'discovery',
        'kirby://page/content/{encodedIdOrUuid}' => 'content.pages.read',
        'kirby://file/content/{encodedIdOrUuid}' => 'content.files.read',
        'kirby://site/content' => 'content.site.read',
        'kirby://user/content/{encodedIdOrEmail}' => 'content.users.read',
        'kirby://info' => 'project.info',
        'kirby://roots' => 'project.roots',
        'kirby://config/{option}' => 'project.config',
        'kirby://blueprint/{encodedId}' => 'project.blueprints',
        'kirby://composer' => 'project.composer',
        'kirby://commands' => 'project.cli',
        'kirby://cli/command/{command}' => 'project.cli',
        'kirby://kb' => 'reference.knowledge',
        'kirby://kb/{path}' => 'reference.knowledge',
        'kirby://susie/{phase}/{step}' => 'reference.knowledge',
        'kirby://glossary' => 'reference.glossary',
        'kirby://glossary/{term}' => 'reference.glossary',
        'kirby://fields/update-schema' => 'reference.schemas',
        'kirby://field/{type}/update-schema' => 'reference.schemas',
        'kirby://blueprints/update-schema' => 'reference.schemas',
        'kirby://blueprint/{type}/update-schema' => 'reference.schemas',
        'kirby://fields' => 'reference.panel',
        'kirby://field/{type}' => 'reference.panel',
        'kirby://sections' => 'reference.panel',
        'kirby://section/{type}' => 'reference.panel',
        'kirby://extensions' => 'reference.extensions',
        'kirby://extension/{name}' => 'reference.extensions',
        'kirby://hooks' => 'reference.hooks',
        'kirby://hook/{name}' => 'reference.hooks',
        'kirby://tool-examples' => 'reference.examples',
        'kirby://uuid/new' => 'reference.uuid',
    ];

    /** @param array<string, bool> $values */
    public function __construct(private array $values)
    {
    }

    /** @return array<string, bool> */
    public static function defaults(): array
    {
        $defaults = ['access' => false];
        foreach (array_merge(array_values(self::TOOLS), array_values(self::RESOURCES)) as $path) {
            foreach (self::ancestors($path) as $key) {
                $defaults[$key] = false;
            }
        }
        ksort($defaults);
        return $defaults;
    }

    /** @return list<string> */
    private static function ancestors(string $path): array
    {
        $parts = explode('.', $path);
        $keys = [];
        while ($parts !== []) {
            $keys[] = implode('.', $parts);
            array_pop($parts);
        }
        return $keys;
    }

    public function allows(?string $path): bool
    {
        if ($path === null || !array_key_exists($path, self::defaults()) || ($this->values['access'] ?? false) !== true) {
            return false;
        }
        foreach (self::ancestors($path) as $key) {
            if (($this->values[$key] ?? false) !== true) {
                return false;
            }
        }
        return true;
    }

    public function tool(string $name): bool
    {
        return $this->allows(self::TOOLS[$name] ?? null);
    }

    public function resource(string $uri): bool
    {
        if (isset(self::RESOURCES[$uri])) {
            return $this->allows(self::RESOURCES[$uri]);
        }
        foreach (self::RESOURCES as $template => $path) {
            if (!str_contains($template, '{')) {
                continue;
            }
            $pattern = preg_replace('/\\\\\{[^}]+\\\\\}/', '[^/]+', preg_quote($template, '#'));
            if (is_string($pattern) && preg_match('#^' . $pattern . '$#D', $uri) === 1) {
                return $this->allows($path);
            }
        }
        return false;
    }

    public function indexItem(string $kind, string $name): bool
    {
        return $kind === 'tool' ? $this->tool($name) : $this->resource($name);
    }

    public function filterRegistry(Registry $registry): void
    {
        foreach ($registry->getTools()->references as $tool) {
            if (!$this->tool($tool->name)) {
                $registry->unregisterTool($tool->name);
            }
        }
        foreach ($registry->getResources()->references as $resource) {
            if (!$this->resource($resource->uri)) {
                $registry->unregisterResource($resource->uri);
            }
        }
        foreach ($registry->getResourceTemplates()->references as $template) {
            if (!$this->resource($template->uriTemplate)) {
                $registry->unregisterResourceTemplate($template->uriTemplate);
            }
        }
    }

    /** @param array<string, mixed> $message */
    public function request(array $message): bool
    {
        $params = $message['params'] ?? [];
        if (!is_array($params)) {
            return false;
        }
        $name = $params['name'] ?? '';
        $uri = $params['uri'] ?? '';
        switch ($message['method'] ?? '') {
            case 'tools/call':
                return is_string($name) && $this->tool($name);
            case 'resources/read':
            case 'resources/subscribe':
            case 'resources/unsubscribe':
                return is_string($uri) && $this->resource($uri);
            case 'completion/complete':
                $ref = $params['ref'] ?? [];
                return is_array($ref) && ($ref['type'] ?? '') === 'ref/resource'
                    && is_string($ref['uri'] ?? null) && $this->resource($ref['uri']);
            case 'subscriptions/listen':
                $uris = $params['notifications']['resourceSubscriptions'] ?? [];
                if (!is_array($uris)) {
                    return false;
                }
                foreach ($uris as $subscription) {
                    if (!is_string($subscription) || !$this->resource($subscription)) {
                        return false;
                    }
                }
                return true;
            default:
                return $this->allows('access');
        }
    }
}
