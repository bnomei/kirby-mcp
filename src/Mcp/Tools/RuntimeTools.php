<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class RuntimeTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Installs Kirby MCP runtime CLI commands into the project (e.g. `mcp:render`).
     *
     * @return array{
     *   projectRoot: string,
     *   commandsRoot: string,
     *   installed: array<int, string>,
     *   skipped: array<int, string>,
     *   errors: array<int, array{path: string, error: string}>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Run once per project to install/update the Kirby MCP runtime CLI commands into the project (site/commands or commands.local). Required for runtime-backed tools like page render/content.',
        keywords: [
            'runtime' => 70,
            'install' => 90,
            'update' => 40,
            'commands' => 60,
            'mcp' => 30,
            'mcp:render' => 40,
            'mcp:page:update' => 30,
            'mcp:page:content' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_runtime_install',
        description: 'Install project-local Kirby CLI commands used by Kirby MCP (e.g. `mcp:render`) into the Kirby project. Run this once per project (writes to site/commands or commands.local).',
        annotations: new ToolAnnotations(
            title: 'Install Runtime Commands',
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: true,
            openWorldHint: false,
        ),
    )]
    public function runtimeInstall(?ClientGateway $client = null, bool $force = false): array
    {
        try {
            $projectRoot = $this->context->projectRoot();

            return (new RuntimeCommandsInstaller())->install($projectRoot, $force)->toArray();
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_runtime_install',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * Check whether the installed Kirby MCP runtime commands are present (expected command wrappers exist).
     *
     * @return array{
     *   projectRoot: string,
     *   host: string|null,
     *   commandsRoot: string,
     *   mcpCommandsDir: string,
     *   installed: bool,
     *   inSync: bool,
     *   expectedFiles: array<int, string>,
     *   installedFiles: array<int, string>,
     *   missingFiles: array<int, string>,
     *   message: string
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to check whether the project-local Kirby MCP runtime commands are installed (expected command wrapper files exist).',
        keywords: [
            'runtime' => 50,
            'status' => 100,
            'sync' => 60,
            'drift' => 40,
            'install' => 30,
            'update' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_runtime_status',
        description: 'Check whether project-local Kirby MCP runtime CLI command wrappers are installed (presence check against the package’s expected command files).',
        annotations: new ToolAnnotations(
            title: 'Runtime Status',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function runtimeStatus(): array
    {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

        $commandsRoot = (new KirbyRootsInspector())->inspect($projectRoot, $host)->commandsRoot()
            ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';

        $mcpCommandsDir = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp';

        $packageRoot = dirname(__DIR__, 3);
        $sourceRoot = rtrim($packageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'commands';
        $expectedFiles = $this->expectedCommandFiles($sourceRoot);

        if ($expectedFiles === []) {
            return [
                'projectRoot' => $projectRoot,
                'host' => $host,
                'commandsRoot' => $commandsRoot,
                'mcpCommandsDir' => $mcpCommandsDir,
                'installed' => false,
                'inSync' => false,
                'expectedFiles' => [],
                'installedFiles' => [],
                'missingFiles' => [],
                'message' => 'Package runtime commands directory missing or contains no PHP command files.',
            ];
        }

        $installedFiles = [];
        $missingFiles = [];

        $commandsRoot = rtrim($commandsRoot, DIRECTORY_SEPARATOR);
        foreach ($expectedFiles as $relativePath) {
            $absolutePath = $commandsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (is_file($absolutePath)) {
                $installedFiles[] = $relativePath;
                continue;
            }

            $missingFiles[] = $relativePath;
        }

        sort($installedFiles);
        sort($missingFiles);

        $installed = $installedFiles !== [];
        $inSync = $installed === true && $missingFiles === [];

        $message = $inSync === true
            ? 'Runtime commands are installed.'
            : ($installed === false
                ? 'Runtime commands are not installed. Run kirby_runtime_install (or `kirby mcp:install` once installed).'
                : 'Runtime commands are partially installed. Run kirby_runtime_install (or `kirby mcp:update`) to install missing command files.');

        return [
            'projectRoot' => $projectRoot,
            'host' => $host,
            'commandsRoot' => $commandsRoot,
            'mcpCommandsDir' => $mcpCommandsDir,
            'installed' => $installed,
            'inSync' => $inSync,
            'expectedFiles' => $expectedFiles,
            'installedFiles' => $installedFiles,
            'missingFiles' => $missingFiles,
            'message' => $message,
        ];
    }

    /**
     * Renders a Kirby page via the CLI runtime command `mcp:render`.
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to render a Kirby page (by id or uuid) via the installed CLI runtime command and capture HTML + errors for debugging/verification.',
        keywords: [
            'render' => 100,
            'page' => 70,
            'html' => 50,
            'preview' => 50,
            'output' => 40,
            'error' => 40,
            'debug' => 30,
            'uuid' => 20,
            'representation' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_render_page',
        description: 'Render a Kirby page by id or uuid via the installed `kirby mcp:render` CLI command and return structured JSON (HTML + errors). Requires `kirby_runtime_install` first.',
        annotations: new ToolAnnotations(
            title: 'Render Page (CLI)',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function renderPage(
        ?string $id = null,
        string $contentType = 'html',
        int $maxChars = 20000,
        bool $noCache = false,
        bool $debug = false,
    ): array {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

        $commandsRoot = $this->commandsRoot($projectRoot, $host);
        $expectedCommandFile = $this->commandFile($commandsRoot, 'mcp/render.php');

        if (!is_file($expectedCommandFile)) {
            return [
                'ok' => false,
                'needsRuntimeInstall' => true,
                'message' => 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.',
                'expectedCommandFile' => $expectedCommandFile,
            ];
        }

        $args = ['mcp:render'];
        if (is_string($id) && $id !== '') {
            $args[] = $id;
        }

        $args[] = '--type=' . $contentType;
        $args[] = '--max=' . max(0, $maxChars);

        if ($noCache === true) {
            $args[] = '--no-cache';
        }

        if ($debug === true) {
            $args[] = '--debug';
        }

        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: $args,
            env: $env,
            timeoutSeconds: 60,
        );

        $parseError = null;
        try {
            $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        } catch (\Throwable $exception) {
            $payload = null;
            $parseError = $exception->getMessage();
        }

        if (!is_array($payload)) {
            return [
                'ok' => false,
                'parseError' => $parseError ?? 'Unable to parse JSON output from Kirby CLI command.',
                'cli' => $cliResult->toArray(),
            ];
        }

        /** @var array<mixed> $payload */
        return array_merge($payload, [
            'cli' => $cliResult->toArray(),
        ]);
    }

    /**
     * Read a page's current content (drafts/changes-aware) via the runtime CLI command `mcp:page:content`.
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to read a page’s current content (drafts/changes-aware) via the installed runtime CLI command (safer than reading content files directly).',
        keywords: [
            'content' => 100,
            'read' => 80,
            'page' => 50,
            'fields' => 40,
            'draft' => 30,
            'uuid' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_read_page_content',
        description: 'Read a page’s content (current version; drafts/changes-aware) by id or uuid via the installed `kirby mcp:page:content` CLI command. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Read Page Content',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function readPageContent(
        ?string $id = null,
        ?string $language = null,
        int $maxCharsPerField = 20000,
    ): array {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();
        $commandsRoot = $this->commandsRoot($projectRoot, $host);
        $expectedCommandFile = $this->commandFile($commandsRoot, 'mcp/page/content.php');

        if (!is_file($expectedCommandFile)) {
            return [
                'ok' => false,
                'needsRuntimeInstall' => true,
                'message' => 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.',
                'expectedCommandFile' => $expectedCommandFile,
            ];
        }

        $args = ['mcp:page:content'];
        if (is_string($id) && $id !== '') {
            $args[] = $id;
        }

        $maxCharsPerField = max(0, $maxCharsPerField);
        $args[] = '--max=' . $maxCharsPerField;

        if (is_string($language) && trim($language) !== '') {
            $args[] = '--language=' . trim($language);
        }

        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: $args,
            env: $env,
            timeoutSeconds: 60,
        );

        $parseError = null;
        try {
            $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        } catch (\Throwable $exception) {
            $payload = null;
            $parseError = $exception->getMessage();
        }

        if (!is_array($payload)) {
            return [
                'ok' => false,
                'parseError' => $parseError ?? 'Unable to parse JSON output from Kirby CLI command.',
                'cli' => $cliResult->toArray(),
            ];
        }

        /** @var array<mixed> $payload */
        return array_merge($payload, [
            'cli' => $cliResult->toArray(),
        ]);
    }

    /**
     * Update a page's content via the runtime CLI command `mcp:page:update`.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to update page content (by id or uuid) via Kirby runtime, with explicit confirm=true guard.',
        keywords: [
            'update' => 100,
            'content' => 80,
            'write' => 70,
            'save' => 60,
            'page' => 50,
            'panel' => 30,
            'confirm' => 30,
            'uuid' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_update_page_content',
        description: 'Update a page’s content by id or uuid via the installed `kirby mcp:page:update` CLI command. Requires confirm=true and kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Update Page Content',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function updatePageContent(
        string $id,
        array $data,
        bool $confirm = false,
        bool $validate = false,
        ?string $language = null,
        int $maxCharsPerField = 20000,
    ): array {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

        $id = trim($id);
        if ($id === '') {
            return [
                'ok' => false,
                'message' => 'id must not be empty.',
            ];
        }

        $commandsRoot = $this->commandsRoot($projectRoot, $host);
        $expectedCommandFile = $this->commandFile($commandsRoot, 'mcp/page/update.php');

        if (!is_file($expectedCommandFile)) {
            return [
                'ok' => false,
                'needsRuntimeInstall' => true,
                'message' => 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.',
                'expectedCommandFile' => $expectedCommandFile,
            ];
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return [
                'ok' => false,
                'message' => 'Unable to encode data to JSON: ' . $exception->getMessage(),
            ];
        }

        $args = [
            'mcp:page:update',
            $id,
            '--data=' . $json,
            '--max=' . $maxCharsPerField,
        ];

        if ($validate === true) {
            $args[] = '--validate';
        }

        if ($confirm === true) {
            $args[] = '--confirm';
        }

        if (is_string($language) && trim($language) !== '') {
            $args[] = '--language=' . trim($language);
        }

        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: $args,
            env: $env,
            timeoutSeconds: 60,
        );

        $parseError = null;
        try {
            $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        } catch (\Throwable $exception) {
            $payload = null;
            $parseError = $exception->getMessage();
        }

        if (!is_array($payload)) {
            return [
                'ok' => false,
                'parseError' => $parseError ?? 'Unable to parse JSON output from Kirby CLI command.',
                'cli' => $cliResult->toArray(),
            ];
        }

        /** @var array<mixed> $payload */
        return array_merge($payload, [
            'cli' => $cliResult->toArray(),
        ]);
    }

    /**
     * List blueprint ids available at runtime (extensions + filesystem) via `mcp:blueprints`.
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to list blueprint ids that Kirby has loaded at runtime (including plugin-registered ones) and whether a filesystem blueprint overrides them.',
        keywords: [
            'blueprints' => 60,
            'loaded' => 100,
            'runtime' => 60,
            'extensions' => 40,
            'plugin' => 30,
            'override' => 40,
            'overrides' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_blueprints_loaded',
        description: 'List blueprint ids that Kirby knows about at runtime (extensions + filesystem). Defaults to idsOnly=true to avoid truncation; supports filters and pagination. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Loaded Blueprints',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function blueprintsLoaded(
        bool $idsOnly = true,
        ?string $type = null,
        ?string $activeSource = null,
        bool $overriddenOnly = false,
        int $limit = 0,
        int $cursor = 0,
        bool $withDisplayName = false,
        bool $debug = false,
    ): array {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();
        $commandsRoot = $this->commandsRoot($projectRoot, $host);
        $expectedCommandFile = $this->commandFile($commandsRoot, 'mcp/blueprints.php');

        if (!is_file($expectedCommandFile)) {
            return [
                'ok' => false,
                'needsRuntimeInstall' => true,
                'message' => 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.',
                'expectedCommandFile' => $expectedCommandFile,
            ];
        }

        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $args = ['mcp:blueprints'];
        if ($idsOnly === true) {
            $args[] = '--ids-only';
        } elseif ($withDisplayName === true) {
            $args[] = '--with-display-name';
        }

        if (is_string($type) && trim($type) !== '') {
            $args[] = '--type=' . trim($type);
        }

        if (is_string($activeSource) && trim($activeSource) !== '') {
            $args[] = '--active-source=' . trim($activeSource);
        }

        if ($overriddenOnly === true) {
            $args[] = '--overridden-only';
        }

        if ($cursor > 0) {
            $args[] = '--cursor=' . $cursor;
        }

        if ($limit > 0) {
            $args[] = '--limit=' . $limit;
        }

        if ($debug === true) {
            $args[] = '--debug';
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: $args,
            env: $env,
            timeoutSeconds: 60,
        );

        $parseError = null;
        try {
            $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        } catch (\Throwable $exception) {
            $payload = null;
            $parseError = $exception->getMessage();
        }

        if (!is_array($payload)) {
            return [
                'ok' => false,
                'parseError' => $parseError ?? 'Unable to parse JSON output from Kirby CLI command.',
                'cliMeta' => [
                    'exitCode' => $cliResult->exitCode,
                    'timedOut' => $cliResult->timedOut,
                ],
                'message' => $debug === true
                    ? null
                    : 'Retry with debug=true to include CLI stdout/stderr.',
                'cli' => $debug === true ? $cliResult->toArray() : null,
            ];
        }

        /** @var array<string, mixed> $payload */
        $response = $payload;
        $response['cliMeta'] = [
            'exitCode' => $cliResult->exitCode,
            'timedOut' => $cliResult->timedOut,
        ];

        if ($debug === true) {
            $response['cli'] = $cliResult->toArray();
        }

        return $response;
    }

    private function commandsRoot(string $projectRoot, ?string $host = null): string
    {
        return (new KirbyRootsInspector())->inspect($projectRoot, $host)->commandsRoot()
            ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
    }

    private function commandFile(string $commandsRoot, string $relativePath): string
    {
        return rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * @return array<int, string>
     */
    private function expectedCommandFiles(string $sourceRoot): array
    {
        $sourceRoot = rtrim($sourceRoot, DIRECTORY_SEPARATOR);
        if ($sourceRoot === '' || !is_dir($sourceRoot)) {
            return [];
        }

        $expected = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceRoot));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = ltrim(substr($absolutePath, strlen($sourceRoot)), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            if ($relativePath !== '') {
                $expected[] = $relativePath;
            }
        }

        $expected = array_values(array_unique($expected));
        sort($expected);

        return $expected;
    }
}
