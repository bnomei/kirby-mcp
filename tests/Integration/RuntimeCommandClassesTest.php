<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\Commands\Blueprint;
use Bnomei\KirbyMcp\Mcp\Commands\Blueprints;
use Bnomei\KirbyMcp\Mcp\Commands\CliCommands;
use Bnomei\KirbyMcp\Mcp\Commands\Collections;
use Bnomei\KirbyMcp\Mcp\Commands\ConfigGet;
use Bnomei\KirbyMcp\Mcp\Commands\Controllers;
use Bnomei\KirbyMcp\Mcp\Commands\EvalPhp;
use Bnomei\KirbyMcp\Mcp\Commands\Install;
use Bnomei\KirbyMcp\Mcp\Commands\Models;
use Bnomei\KirbyMcp\Mcp\Commands\PageContent;
use Bnomei\KirbyMcp\Mcp\Commands\PageUpdate;
use Bnomei\KirbyMcp\Mcp\Commands\Plugins;
use Bnomei\KirbyMcp\Mcp\Commands\Render;
use Bnomei\KirbyMcp\Mcp\Commands\Routes;
use Bnomei\KirbyMcp\Mcp\Commands\Snippets;
use Bnomei\KirbyMcp\Mcp\Commands\Templates;
use Bnomei\KirbyMcp\Mcp\Commands\Update;
use Kirby\CLI\CLI;
use Kirby\Cms\App;
use League\CLImate\CLImate;

final class RuntimeCommandIntegrationClimate extends CLImate
{
    /**
     * @var array<int, array{method: string, message: string}>
     */
    public array $messages = [];

    public function __call($requested_method, $arguments)
    {
        if (in_array($requested_method, ['error', 'green', 'out'], true)) {
            $message = $arguments[0] ?? '';
            $this->messages[] = [
                'method' => $requested_method,
                'message' => is_string($message) ? $message : (json_encode($message) ?: ''),
            ];

            return $this;
        }

        return parent::__call($requested_method, $arguments);
    }
}

final class RuntimeCommandIntegrationCli extends CLI
{
    /**
     * @param array<string, mixed> $args
     * @param array<string, array<int, string>> $commands
     * @param array<string, array<string, mixed>> $definitions
     */
    public function __construct(
        private array $args = [],
        ?App $kirby = null,
        private ?string $cwd = null,
        ?CLImate $climate = null,
        private array $commands = [],
        private array $definitions = [],
        private string $cliVersion = '0.0.0-test',
    ) {
        $this->kirby = $kirby;
        $this->cwd = $cwd ?? cmsPath();
        $this->climate = $climate ?? new RuntimeCommandIntegrationClimate();
        $this->options = [];
        $this->roots = [];
    }

    public function arg(string $name): mixed
    {
        return $this->args[$name] ?? null;
    }

    public function kirby(bool $fail = true): ?App
    {
        return $this->kirby;
    }

    public function json(array $data = []): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    public function dir(?string $folder = null): string
    {
        if ($folder === null || $folder === '') {
            return $this->cwd ?? cmsPath();
        }

        if (str_starts_with($folder, '.')) {
            return rtrim($this->cwd ?? cmsPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($folder, DIRECTORY_SEPARATOR);
        }

        return $folder;
    }

    public function commands(): array
    {
        return $this->commands;
    }

    public function load(string $name): callable|array
    {
        if (!array_key_exists($name, $this->definitions)) {
            throw new \RuntimeException('Command not found.');
        }

        return $this->definitions[$name];
    }

    public function version(): string
    {
        return $this->cliVersion;
    }
}

/**
 * @return array{0: App, 1: App|null, 2: array<int, callable>, 3: bool}
 */
function runtimeCommandsApp(?string $commandsRoot = null): array
{
    $previous = App::instance(null, true);
    $previousErrorHandlers = runtimeCommandsErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;

    $roots = [
        'index' => cmsPath(),
    ];

    if (is_string($commandsRoot) && $commandsRoot !== '') {
        $roots['commands'] = $commandsRoot;
    }

    $app = new App([
        'roots' => $roots,
    ]);

    return [$app, $previous, $previousErrorHandlers, $previousWhoops];
}

/**
 * @param array<int, callable> $previousErrorHandlers
 */
function restoreRuntimeCommandsApp(?App $previous, array $previousErrorHandlers, bool $previousWhoops): void
{
    if ($previous instanceof App) {
        App::instance($previous);
    }

    App::$enableWhoops = $previousWhoops;

    $activeErrorHandlers = runtimeCommandsErrorHandlers();
    foreach ($activeErrorHandlers as $_) {
        restore_error_handler();
    }

    foreach ($previousErrorHandlers as $handler) {
        if (is_callable($handler)) {
            set_error_handler($handler);
        }
    }
}

/**
 * @return array<int, callable>
 */
function runtimeCommandsErrorHandlers(): array
{
    $handlers = [];

    while (true) {
        $previousHandler = set_error_handler(static fn (): bool => false);
        restore_error_handler();

        if ($previousHandler === null) {
            break;
        }

        $handlers[] = $previousHandler;
        restore_error_handler();
    }

    $handlers = array_reverse($handlers);

    foreach ($handlers as $handler) {
        if (is_callable($handler)) {
            set_error_handler($handler);
        }
    }

    return $handlers;
}

/**
 * @return array<mixed>
 */
function runRuntimeCommand(callable $callback): array
{
    ob_start();
    $callback();
    $output = ob_get_clean();

    $payload = McpMarkedJsonExtractor::extract($output ?: '');

    expect($payload)->toBeArray();

    return $payload;
}

function runtimeCommandsTempDir(string $suffix): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-runtime-' . $suffix . '-' . bin2hex(random_bytes(4));
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir;
}

function removeRuntimeCommandsDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}

it('reads config options via the config:get command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'path' => 'vendorname.pluginname.someoption',
            ], $app);

            ConfigGet::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['path'])->toBe('vendorname.pluginname.someoption');
        expect($payload['value'])->toBe('5');

        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'path' => json_encode(['vendorname', 'pluginname', 'arrayoption']),
            ], $app);

            ConfigGet::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['path'])->toBe('vendorname.pluginname.arrayoption');
        expect($payload['value'])->toContain('"a":1');

        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'path' => 'vendorname.pluginname.closureoption',
            ], $app);

            ConfigGet::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['value'])->toBe('Closure type');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('accepts JSON list paths in the config:get command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'path' => '["debug"]',
            ], $app);

            ConfigGet::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['path'])->toBe('debug');
        expect($payload['line'])->toContain('debug =');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('prefixes config:get output with the host when KIRBY_HOST is set', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();
    $originalHost = getenv('KIRBY_HOST');

    putenv('KIRBY_HOST=https://example.test');

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'path' => 'debug',
            ], $app);

            ConfigGet::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['host'])->toBe('https://example.test');
        expect($payload['line'])->toStartWith('[https://example.test] ');
    } finally {
        if ($originalHost === false) {
            putenv('KIRBY_HOST');
        } else {
            putenv('KIRBY_HOST=' . $originalHost);
        }

        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('rejects JSON object inputs that resolve to multiple config paths', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'path' => '{"a": 1, "b": 2}',
            ], $app);

            ConfigGet::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['error']['message'] ?? '')->toContain('exactly one option path');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('reads a blueprint via the blueprint command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'pages/home',
            ], $app);

            Blueprint::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['id'])->toBe('pages/home');
        expect($payload['type'])->toBe('pages');
        expect($payload['displayName'])->toBe('Home');
        expect($payload['data'])->toBeArray();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('rejects missing blueprint ids via the blueprint command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Blueprint::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['error']['message'] ?? '')->toContain('Blueprint id is required');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('rejects invalid blueprint ids via the blueprint command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'pages/../home',
            ], $app);

            Blueprint::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['error']['message'] ?? '')->toContain('Blueprint id');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists blueprint ids via the blueprints command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Blueprints::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['idsOnly'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['blueprints'],
        );

        expect($ids)->toContain('pages/home');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists blueprint details with display names via the blueprints command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'withDisplayName' => true,
            ], $app);

            Blueprints::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['withDisplayName'])->toBeTrue();

        $byId = [];
        foreach ($payload['blueprints'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $home = $byId['pages/home'] ?? null;
        expect($home)->toBeArray();
        expect($home['displayName'] ?? null)->toBe('Home');
        expect($home['displayNameSource'] ?? null)->toBe('title');
        expect($home['data'] ?? null)->toBeNull();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists blueprint data when withData=true via the blueprints command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'withData' => true,
            ], $app);

            Blueprints::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['withData'])->toBeTrue();
        expect($payload['counts']['withData'] ?? 0)->toBeGreaterThan(0);

        $byId = [];
        foreach ($payload['blueprints'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $home = $byId['pages/home'] ?? null;
        expect($home)->toBeArray();
        expect($home['data'] ?? null)->toBeArray();
        expect($home['fieldSchemas'] ?? null)->toBeArray();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists collection ids via the collections command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Collections::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['collections'],
        );

        expect($ids)->toContain('notes');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists collection details when idsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Collections::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['collectionsRoot'])->toBeString();

        $byId = [];
        foreach ($payload['collections'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $notes = $byId['notes'] ?? null;
        expect($notes)->toBeArray();
        expect($notes['activeSource'] ?? null)->toBe('file');
        expect((string) ($notes['file']['collectionsRoot']['relativeToCollectionsRoot'] ?? ''))->toContain('notes');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('filters collections by activeSource=extension', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'activeSource' => 'extension',
            ], $app);

            Collections::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['filters'])->toHaveKey('activeSource', 'extension');
        expect($payload['collections'])->toBe([]);
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('paginates collections with limit', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'limit' => 1,
            ], $app);

            Collections::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['pagination']['limit'])->toBe(1);
        expect($payload['pagination']['returned'])->toBe(1);
        expect($payload['pagination']['total'])->toBeGreaterThanOrEqual(1);
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists controller ids via the controllers command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Controllers::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['controllers'],
        );

        expect($ids)->toContain('notes');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists controller details when idsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Controllers::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $byId = [];
        foreach ($payload['controllers'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $notes = $byId['notes'] ?? null;
        expect($notes)->toBeArray();
        expect($notes['name'] ?? null)->toBe('notes');
        expect((string) ($notes['file']['controllersRoot']['relativeToControllersRoot'] ?? ''))->toContain('notes');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists model ids via the models command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Models::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['models'],
        );

        expect($ids)->toContain('album');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists model details when idsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Models::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $byId = [];
        foreach ($payload['models'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $album = $byId['album'] ?? null;
        expect($album)->toBeArray();
        expect($album['class'] ?? null)->toBeString()->not()->toBe('');
        expect($album['activeSource'] ?? null)->toBe('file');
        expect((string) ($album['file']['modelsRoot']['relativeToModelsRoot'] ?? ''))->toContain('album');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists snippet ids via the snippets command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Snippets::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['snippets'],
        );

        expect($ids)->toContain('header');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists snippet details when idsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Snippets::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $byId = [];
        foreach ($payload['snippets'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $header = $byId['header'] ?? null;
        expect($header)->toBeArray();
        expect($header['activeSource'] ?? null)->toBe('file');
        expect((string) ($header['file']['snippetsRoot']['relativeToSnippetsRoot'] ?? ''))->toContain('header');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists template ids via the templates command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Templates::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['templates'],
        );

        expect($ids)->toContain('home');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists template details when idsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Templates::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $byId = [];
        foreach ($payload['templates'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $home = $byId['home'] ?? null;
        expect($home)->toBeArray();
        expect($home['activeSource'] ?? null)->toBe('file');
        expect($home['name'] ?? null)->toBe('home');
        expect((string) ($home['file']['templatesRoot']['relativeToTemplatesRoot'] ?? ''))->toContain('home');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists plugin ids via the plugins command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'idsOnly' => true,
            ], $app);

            Plugins::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $ids = array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $payload['plugins'],
        );

        expect($ids)->toContain('mcp/test-routes');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists plugin details when idsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            Plugins::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $byId = [];
        foreach ($payload['plugins'] as $entry) {
            $id = $entry['id'] ?? null;
            if (is_string($id) && $id !== '') {
                $byId[$id] = $entry;
            }
        }

        $plugin = $byId['mcp/test-routes'] ?? null;
        expect($plugin)->toBeArray();
        expect($plugin['plugin']['prefix'] ?? null)->toBe('mcp.test-routes');
        expect((string) ($plugin['rootRelativePath'] ?? ''))->toContain('mcp-test');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists routes via the routes command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'patternsOnly' => true,
                'patternContains' => 'mcp-test',
            ], $app);

            Routes::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $patterns = array_map(
            static fn (array $route): string => (string) ($route['pattern'] ?? ''),
            $payload['routes'],
        );

        expect($patterns)->toContain('mcp-test/config-route');
        expect($patterns)->toContain('mcp-test/plugin-route');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('returns route source details when patternsOnly=false', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'patternContains' => 'mcp-test',
                'method' => 'GET',
            ], $app);

            Routes::run($cli);
        });

        expect($payload['ok'])->toBeTrue();

        $byPattern = [];
        foreach ($payload['routes'] as $route) {
            $pattern = $route['pattern'] ?? null;
            if (is_string($pattern) && $pattern !== '') {
                $byPattern[$pattern] = $route;
            }
        }

        $configRoute = $byPattern['mcp-test/config-route'] ?? null;
        expect($configRoute)->toBeArray();
        expect($configRoute)->toHaveKey('action');
        expect($configRoute)->toHaveKey('source.kind', 'config');
        expect((string) ($configRoute['source']['relativePath'] ?? ''))->toContain(
            'site' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php',
        );

        $pluginRoute = $byPattern['mcp-test/plugin-route'] ?? null;
        expect($pluginRoute)->toBeArray();
        expect($pluginRoute)->toHaveKey('action');
        expect($pluginRoute)->toHaveKey('source.kind', 'plugin');
        expect($pluginRoute)->toHaveKey('source.pluginId', 'mcp/test-routes');
        expect((string) ($pluginRoute['source']['relativePath'] ?? ''))->toContain(
            'site' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'mcp-test' . DIRECTORY_SEPARATOR . 'index.php',
        );
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('paginates routes with limit and cursor', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'limit' => 1,
                'cursor' => 0,
            ], $app);

            Routes::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['pagination']['limit'])->toBe(1);
        expect($payload['pagination']['returned'])->toBe(1);
        expect($payload['pagination']['total'])->toBeGreaterThan(1);
        expect($payload['pagination']['hasMore'])->toBeTrue();
        expect($payload['pagination']['nextCursor'])->toBe(1);
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('reads page content via the page:content command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
            ], $app);

            PageContent::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['page']['id'] ?? null)->toBe('home');
        expect($payload['content'])->toBeArray();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('renders a page via the render command', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
                'type' => 'html',
                'max' => 500,
            ], $app);

            Render::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['contentType'])->toBe('html');
        expect($payload['html'])->toBeString();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('returns an error when render cannot resolve the page', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'does-not-exist',
            ], $app);

            Render::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['error']['message'] ?? '')->toContain('Page not found');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('truncates rendered output when max is set', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'max' => 20,
            ], $app);

            Render::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['truncated'])->toBeTrue();
        expect(strlen((string) ($payload['html'] ?? '')))->toBeLessThanOrEqual(20);
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('returns a dry-run response for page updates without confirm', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
                'data' => json_encode(['headline' => 'Preview only']),
            ], $app);

            PageUpdate::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['needsConfirm'])->toBeTrue();
        expect($payload['updatedKeys'])->toContain('headline');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('rejects invalid page update payloads', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $missing = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
            ], $app);

            PageUpdate::run($cli);
        });

        expect($missing['ok'])->toBeFalse();
        expect($missing['error']['message'] ?? null)->toBe('Missing --data JSON object.');

        $invalid = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
                'data' => '{invalid}',
            ], $app);

            PageUpdate::run($cli);
        });

        expect($invalid['ok'])->toBeFalse();
        expect($invalid['error']['message'] ?? null)->toContain('Invalid JSON for --data');

        $list = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
                'data' => '["headline","invalid"]',
            ], $app);

            PageUpdate::run($cli);
        });

        expect($list['ok'])->toBeFalse();
        expect($list['error']['message'] ?? null)->toBe('--data must be a JSON object with field keys.');
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('updates page content when confirm=true and restores fixture', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();
    $projectRoot = cmsPath();
    $homeContentFile = $projectRoot . '/content/home/home.txt';
    $original = file_get_contents($homeContentFile);

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
                'data' => json_encode(['headline' => 'Command Update']),
                'confirm' => true,
            ], $app);

            PageUpdate::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['content']['headline'] ?? null)->toBe('Command Update');

        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'id' => 'home',
                'data' => json_encode(['{"headline":"Command Update JSON"}']),
                'confirm' => true,
            ], $app);

            PageUpdate::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['content']['headline'] ?? null)->toBe('Command Update JSON');
    } finally {
        if (is_string($original)) {
            file_put_contents($homeContentFile, $original);
        }

        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('reports eval as disabled by default', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            EvalPhp::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['enabled'])->toBeFalse();
        expect($payload['needsEnable'])->toBeTrue();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('returns a dry-run response when eval is enabled without confirm', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();
    $previousEnv = getenv(EvalPhp::ENV_ENABLE_EVAL);
    putenv(EvalPhp::ENV_ENABLE_EVAL . '=1');

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([], $app);

            EvalPhp::run($cli);
        });

        expect($payload['ok'])->toBeFalse();
        expect($payload['enabled'])->toBeTrue();
        expect($payload['needsConfirm'])->toBeTrue();
    } finally {
        if ($previousEnv === false) {
            putenv(EvalPhp::ENV_ENABLE_EVAL);
        } else {
            putenv(EvalPhp::ENV_ENABLE_EVAL . '=' . $previousEnv);
        }

        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('executes eval code when enabled and confirm=true', function (): void {
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp();
    $previousEnv = getenv(EvalPhp::ENV_ENABLE_EVAL);
    putenv(EvalPhp::ENV_ENABLE_EVAL . '=1');

    try {
        $payload = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'confirm' => true,
                'debug' => true,
                'max' => 3,
                'code' => "<?php echo str_repeat('x', 5); return ['value' => 123];",
            ], $app);

            EvalPhp::run($cli);
        });

        expect($payload['ok'])->toBeTrue();
        expect($payload['stdout'])->toBe('xxx');
        expect($payload['stdoutTruncated'])->toBeTrue();
        expect($payload['return']['json'] ?? null)->toBe(['value' => 123]);
        expect($payload['return']['type'] ?? null)->toBe('array');
        expect($payload['code'] ?? null)->toBe("echo str_repeat('x', 5); return ['value' => 123];");

        $missing = runRuntimeCommand(function () use ($app): void {
            $cli = new RuntimeCommandIntegrationCli([
                'confirm' => true,
                'code' => '   ',
            ], $app);

            EvalPhp::run($cli);
        });

        expect($missing['ok'])->toBeFalse();
        expect($missing['error']['message'] ?? null)->toBe('Missing eval code argument.');
    } finally {
        if ($previousEnv === false) {
            putenv(EvalPhp::ENV_ENABLE_EVAL);
        } else {
            putenv(EvalPhp::ENV_ENABLE_EVAL . '=' . $previousEnv);
        }

        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
    }
});

it('lists CLI commands with argument metadata', function (): void {
    $commands = [
        'core' => ['mcp:ping'],
        'global' => [],
        'custom' => [],
        'plugins' => ['mcp:hello'],
    ];

    $definitions = [
        'mcp:ping' => [
            'description' => 'Ping command.',
            'args' => [
                'path' => [
                    'description' => 'Target path.',
                    'required' => true,
                ],
                'force' => [
                    'description' => 'Force run.',
                    'longPrefix' => 'force',
                    'noValue' => true,
                ],
            ],
            'command' => static fn (): bool => true,
        ],
        'mcp:hello' => [
            'description' => 'Hello command.',
            'args' => [],
            'command' => static fn (): bool => true,
        ],
    ];

    $payload = runRuntimeCommand(function () use ($commands, $definitions): void {
        $cli = new RuntimeCommandIntegrationCli(
            args: ['idsOnly' => false],
            kirby: null,
            climate: new RuntimeCommandIntegrationClimate(),
            commands: $commands,
            definitions: $definitions,
            cliVersion: '1.2.3',
        );

        CliCommands::run($cli);
    });

    expect($payload['ok'])->toBeTrue();
    expect($payload['cliVersion'])->toBe('1.2.3');

    $byId = [];
    foreach ($payload['commands'] as $command) {
        $id = $command['id'] ?? null;
        if (is_string($id) && $id !== '') {
            $byId[$id] = $command;
        }
    }

    expect($byId)->toHaveKey('mcp:ping');
    expect($byId['mcp:ping']['args']['required'])->not()->toBeEmpty();
    expect($byId['mcp:ping']['args']['optional'])->not()->toBeEmpty();
});

it('installs runtime commands into a temp commands root', function (): void {
    $commandsRoot = runtimeCommandsTempDir('install');
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp($commandsRoot);
    $climate = new RuntimeCommandIntegrationClimate();

    try {
        $cli = new RuntimeCommandIntegrationCli([
            'force' => true,
        ], $app, cmsPath(), $climate);

        Install::run($cli);

        $messages = $climate->messages;
        $combined = implode("\n", array_map(static fn (array $entry): string => $entry['message'], $messages));

        expect($combined)->toContain('Kirby MCP runtime commands installed');
        expect(is_file($commandsRoot . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'blueprint.php'))->toBeTrue();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
        removeRuntimeCommandsDir($commandsRoot);
    }
});

it('reports an error when install runs without a Kirby app', function (): void {
    $climate = new RuntimeCommandIntegrationClimate();
    $cli = new RuntimeCommandIntegrationCli([], null, cmsPath(), $climate);

    Install::run($cli);

    $messages = $climate->messages;
    $combined = implode("\n", array_map(static fn (array $entry): string => $entry['message'], $messages));

    expect($combined)->toContain('The Kirby installation could not be found.');
});

it('updates runtime commands into a temp commands root', function (): void {
    $commandsRoot = runtimeCommandsTempDir('update');
    [$app, $previous, $errorHandlers, $previousWhoops] = runtimeCommandsApp($commandsRoot);
    $climate = new RuntimeCommandIntegrationClimate();

    try {
        $cli = new RuntimeCommandIntegrationCli([], $app, cmsPath(), $climate);

        Update::run($cli);

        $messages = $climate->messages;
        $combined = implode("\n", array_map(static fn (array $entry): string => $entry['message'], $messages));

        expect($combined)->toContain('Kirby MCP runtime commands updated');
        expect($combined)->toContain('Missing (before):');
        expect(is_file($commandsRoot . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'commands.php'))->toBeTrue();
    } finally {
        restoreRuntimeCommandsApp($previous, $errorHandlers, $previousWhoops);
        removeRuntimeCommandsDir($commandsRoot);
    }
});

it('reports an error when update runs without a Kirby app', function (): void {
    $climate = new RuntimeCommandIntegrationClimate();
    $cli = new RuntimeCommandIntegrationCli([], null, cmsPath(), $climate);

    Update::run($cli);

    $messages = $climate->messages;
    $combined = implode("\n", array_map(static fn (array $entry): string => $entry['message'], $messages));

    expect($combined)->toContain('The Kirby installation could not be found.');
});
