<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Dumps\McpDumpContext;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\DumpState;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandResult;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class RuntimeTools
{
    public const ENV_ENABLE_EVAL = 'KIRBY_MCP_ENABLE_EVAL';

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

            $result = (new RuntimeCommandsInstaller())->install($projectRoot, $force);
            StaticCache::clearPrefix('cli:');
            StaticCache::clearPrefix('completion:');

            return $result->toArray();
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
        $runtime = new KirbyRuntimeContext($this->context);
        $projectRoot = $runtime->projectRoot();
        $host = $runtime->host();
        $commandsRoot = $runtime->commandsRoot();

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
        $traceId = McpDumpContext::generateTraceId();
        DumpState::setLastTraceId($traceId);

        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context, [
            'KIRBY_MCP_TRACE_ID' => $traceId,
        ]));

        $args = [RuntimeCommands::RENDER];
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

        $result = $runner->runMarkedJson(RuntimeCommands::RENDER_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return array_merge($result->needsRuntimeInstallResponse(), [
                'traceId' => $traceId,
            ]);
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'cli' => $result->cli(),
                'traceId' => $traceId,
            ]);
        }

        return array_merge($result->payload, [
            'cli' => $result->cli(),
            'traceId' => $traceId,
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
        description: 'Read a page’s content (current version; drafts/changes-aware) by id or uuid via the installed `kirby mcp:page:content` CLI command. Requires kirby_runtime_install first. Resource template: `kirby://page/content/{encodedIdOrUuid}`.',
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
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $args = [RuntimeCommands::PAGE_CONTENT];
        if (is_string($id) && $id !== '') {
            $args[] = $id;
        }

        $maxCharsPerField = max(0, $maxCharsPerField);
        $args[] = '--max=' . $maxCharsPerField;

        if (is_string($language) && trim($language) !== '') {
            $args[] = '--language=' . trim($language);
        }

        $result = $runner->runMarkedJson(RuntimeCommands::PAGE_CONTENT_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $result->needsRuntimeInstallResponse();
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]);
        }

        return array_merge($result->payload, [
            'cli' => $result->cli(),
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
        description: 'Update a page’s content by id or uuid via the installed `kirby mcp:page:update` CLI command. `data` must be a JSON object mapping field keys to values (NOT an array), e.g. `{"title":"Hello","text":"..."}`; it uses Kirby’s `$page->update($data, $language, $validate)` semantics. Recommended flow: call once with `confirm=false` to get a preview (`needsConfirm=true`, `updatedKeys`), then call again with `confirm=true` to actually write. Optional: `validate=true` to enforce blueprint rules; `language` to target a language. Requires kirby_runtime_install first.',
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
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $id = trim($id);
        if ($id === '') {
            return [
                'ok' => false,
                'message' => 'id must not be empty.',
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
            RuntimeCommands::PAGE_UPDATE,
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

        $result = $runner->runMarkedJson(RuntimeCommands::PAGE_UPDATE_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $result->needsRuntimeInstallResponse();
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]);
        }

        return array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);
    }

    /**
     * Execute PHP code inside Kirby runtime via the installed CLI command `mcp:eval`.
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use like `tinker` / a REPL for quick inspection in Kirby runtime (execute small PHP snippets in project context). Disabled by default; requires explicit enable + confirm.',
        keywords: [
            'eval' => 100,
            'tinker' => 80,
            'php -r' => 70,
            'execute' => 60,
            'inspect' => 50,
            'debug' => 40,
            'repl' => 30,
            'runtime' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_eval',
        description: 'Tinker/REPL (`tinker`): Execute PHP code in Kirby runtime via the installed `kirby mcp:eval` CLI command and return structured JSON (captured stdout + return value). Call it repeatedly like a REPL for quick inspection/debugging; tip: end with `return ...;` to capture a value. Disabled by default; enable via env `KIRBY_MCP_ENABLE_EVAL=1` or `.kirby-mcp/mcp.json` `{\"eval\":{\"enabled\":true}}`. Requires confirm=true and kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Eval (CLI)',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function evalPhp(
        string $code,
        bool $confirm = false,
        int $maxChars = 20000,
        int $timeoutSeconds = 60,
        bool $debug = false,
    ): array {
        $projectRoot = $this->context->projectRoot();
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $enabled = $this->isEvalEnabled($projectRoot);
        if ($enabled !== true) {
            return [
                'ok' => false,
                'enabled' => false,
                'needsEnable' => true,
                'message' => 'Eval is disabled by default. Enable via env ' . self::ENV_ENABLE_EVAL . '=1 or via .kirby-mcp/mcp.json: {"eval":{"enabled":true}}.',
            ];
        }

        $args = [RuntimeCommands::EVAL, $code, '--max=' . max(0, $maxChars)];

        if ($confirm === true) {
            $args[] = '--confirm';
        }

        if ($debug === true) {
            $args[] = '--debug';
        }

        $result = $runner->runMarkedJson(RuntimeCommands::EVAL_FILE, $args, timeoutSeconds: $timeoutSeconds);

        if ($result->installed !== true) {
            return $result->needsRuntimeInstallResponse();
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'cliMeta' => $result->cliMeta(),
                'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                'cli' => $debug === true ? $result->cli() : null,
            ]);
        }

        /** @var array<string, mixed> $response */
        $response = $result->payload;
        $response['cliMeta'] = $result->cliMeta();

        if ($debug === true) {
            $response['cli'] = $result->cli();
        }

        return $response;
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
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $args = [RuntimeCommands::BLUEPRINTS];
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

        $result = $runner->runMarkedJson(RuntimeCommands::BLUEPRINTS_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $result->needsRuntimeInstallResponse();
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'cliMeta' => $result->cliMeta(),
                'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                'cli' => $debug === true ? $result->cli() : null,
            ]);
        }

        /** @var array<string, mixed> $response */
        $response = $result->payload;
        $response['cliMeta'] = $result->cliMeta();

        if ($debug === true) {
            $response['cli'] = $result->cli();
        }

        return $response;
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

    private function isEvalEnabled(string $projectRoot): bool
    {
        $raw = getenv(self::ENV_ENABLE_EVAL);
        if (is_string($raw) && $raw !== '') {
            $normalized = strtolower(trim($raw));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return KirbyMcpConfig::load($projectRoot)->evalEnabled();
    }
}
