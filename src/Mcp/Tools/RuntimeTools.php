<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Dumps\McpDumpContext;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\DumpState;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\FieldSchemaHelper;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandResult;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Attribute\Schema;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

final class RuntimeTools
{
    use StructuredToolResult;
    public const ENV_ENABLE_EVAL = 'KIRBY_MCP_ENABLE_EVAL';
    public const ENV_ENABLE_QUERY = 'KIRBY_MCP_ENABLE_QUERY';

    private const CLI_RESULT_SCHEMA = [
        'type' => ['object', 'null'],
        'properties' => [
            'exitCode' => ['type' => 'integer'],
            'stdout' => ['type' => 'string'],
            'stderr' => ['type' => 'string'],
            'timedOut' => ['type' => 'boolean'],
        ],
        'additionalProperties' => true,
    ];

    private const READ_PAGE_CONTENT_OUTPUT_SCHEMA = [
        'oneOf' => [
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [true]],
                    'page' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'template' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                        ],
                        'required' => ['id', 'uuid', 'template', 'url'],
                        'additionalProperties' => true,
                    ],
                    'language' => ['type' => ['string', 'null']],
                    'keys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'truncatedKeys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'content' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                    'fieldSchemas' => [
                        'type' => 'array',
                        'items' => ['type' => 'object'],
                    ],
                    'warningBlock' => ['type' => ['object', 'null']],
                    'BEFORE_UPDATE_READ' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'warning' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok', 'page', 'keys', 'truncatedKeys', 'content', 'fieldSchemas', 'warning'],
                'additionalProperties' => true,
            ],
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [false]],
                    'needsRuntimeInstall' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'expectedCommandFile' => ['type' => 'string'],
                    'parseError' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok'],
                'additionalProperties' => true,
            ],
        ],
    ];

    private const READ_SITE_CONTENT_OUTPUT_SCHEMA = [
        'oneOf' => [
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [true]],
                    'site' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'url' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'url'],
                        'additionalProperties' => true,
                    ],
                    'language' => ['type' => ['string', 'null']],
                    'keys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'truncatedKeys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'content' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                    'fieldSchemas' => [
                        'type' => 'array',
                        'items' => ['type' => 'object'],
                    ],
                    'warningBlock' => ['type' => ['object', 'null']],
                    'BEFORE_UPDATE_READ' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'warning' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok', 'site', 'keys', 'truncatedKeys', 'content', 'fieldSchemas', 'warning'],
                'additionalProperties' => true,
            ],
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [false]],
                    'needsRuntimeInstall' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'expectedCommandFile' => ['type' => 'string'],
                    'parseError' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok'],
                'additionalProperties' => true,
            ],
        ],
    ];

    private const READ_FILE_CONTENT_OUTPUT_SCHEMA = [
        'oneOf' => [
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [true]],
                    'file' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'filename' => ['type' => 'string'],
                            'template' => ['type' => ['string', 'null']],
                            'url' => ['type' => 'string'],
                            'parent' => ['type' => ['object', 'null']],
                        ],
                        'required' => ['id', 'uuid', 'filename', 'url'],
                        'additionalProperties' => true,
                    ],
                    'language' => ['type' => ['string', 'null']],
                    'keys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'truncatedKeys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'content' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                    'fieldSchemas' => [
                        'type' => 'array',
                        'items' => ['type' => 'object'],
                    ],
                    'warningBlock' => ['type' => ['object', 'null']],
                    'BEFORE_UPDATE_READ' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'warning' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok', 'file', 'keys', 'truncatedKeys', 'content', 'fieldSchemas', 'warning'],
                'additionalProperties' => true,
            ],
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [false]],
                    'needsRuntimeInstall' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'expectedCommandFile' => ['type' => 'string'],
                    'parseError' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok'],
                'additionalProperties' => true,
            ],
        ],
    ];

    private const READ_USER_CONTENT_OUTPUT_SCHEMA = [
        'oneOf' => [
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [true]],
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'email' => ['type' => 'string'],
                            'name' => ['type' => ['string', 'null']],
                            'role' => ['type' => 'string'],
                        ],
                        'required' => ['id', 'email', 'role'],
                        'additionalProperties' => true,
                    ],
                    'language' => ['type' => ['string', 'null']],
                    'keys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'truncatedKeys' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'content' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                    ],
                    'fieldSchemas' => [
                        'type' => 'array',
                        'items' => ['type' => 'object'],
                    ],
                    'warningBlock' => ['type' => ['object', 'null']],
                    'BEFORE_UPDATE_READ' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'warning' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok', 'user', 'keys', 'truncatedKeys', 'content', 'fieldSchemas', 'warning'],
                'additionalProperties' => true,
            ],
            [
                'type' => 'object',
                'properties' => [
                    'ok' => ['enum' => [false]],
                    'needsRuntimeInstall' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'expectedCommandFile' => ['type' => 'string'],
                    'parseError' => ['type' => 'string'],
                    'cli' => self::CLI_RESULT_SCHEMA,
                ],
                'required' => ['ok'],
                'additionalProperties' => true,
            ],
        ],
    ];

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
        whenToUse: 'Run once per project to install/update the Kirby MCP runtime CLI commands into the project (site/commands or commands.local). Required for runtime-backed tools like render/content for pages, site, files, and users.',
        keywords: [
            'runtime' => 70,
            'install' => 90,
            'update' => 40,
            'commands' => 60,
            'mcp' => 30,
            'mcp:install' => 40,
            'mcp:update' => 40,
            'mcp:render' => 40,
            'mcp:blueprint' => 30,
            'mcp:blueprints' => 30,
            'mcp:cli:commands' => 30,
            'mcp:config:get' => 30,
            'mcp:collections' => 30,
            'mcp:controllers' => 30,
            'mcp:eval' => 30,
            'mcp:query:dot' => 30,
            'mcp:models' => 30,
            'mcp:plugins' => 30,
            'mcp:routes' => 30,
            'mcp:snippets' => 30,
            'mcp:templates' => 30,
            'mcp:page:update' => 30,
            'mcp:page:content' => 30,
            'mcp:site:update' => 30,
            'mcp:site:content' => 30,
            'mcp:file:update' => 30,
            'mcp:file:content' => 30,
            'mcp:user:update' => 30,
            'mcp:user:content' => 30,
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
    public function runtimeInstall(bool $force = false, ?RequestContext $context = null): array|CallToolResult
    {
        try {
            $projectRoot = $this->context->projectRoot();

            $result = (new RuntimeCommandsInstaller())->install($projectRoot, $force);
            StaticCache::clearPrefix('cli:');
            StaticCache::clearPrefix('completion:');

            return $this->maybeStructuredResult($context, $result->toArray());
        } catch (\Throwable $exception) {
            McpLog::error($context, [
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
    public function runtimeStatus(?RequestContext $context = null): array|CallToolResult
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
            return $this->maybeStructuredResult($context, [
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
            ]);
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

        return $this->maybeStructuredResult($context, [
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
        ]);
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
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $traceId = McpDumpContext::generateTraceId();
        DumpState::setLastTraceId($traceId, $context?->getSession());

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
            return $this->maybeStructuredResult($context, array_merge($result->needsRuntimeInstallResponse(), [
                'traceId' => $traceId,
            ]));
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
                'traceId' => $traceId,
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
            'traceId' => $traceId,
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Read a page's current content (drafts/changes-aware) via the runtime CLI command `mcp:page:content`.
     *
     * @return array<string, mixed>|CallToolResult
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
        meta: [
            'outputSchema' => self::READ_PAGE_CONTENT_OUTPUT_SCHEMA,
        ],
    )]
    public function readPageContent(
        ?string $id = null,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
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
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Update a page's content via the runtime CLI command `mcp:page:update`.
     *
     * @param array<string, mixed>|string $data
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
        description: 'Update a page’s content by id or uuid via the installed `kirby mcp:page:update` CLI command. PREREQUISITE: Read `kirby://blueprint/page/update-schema` plus `kirby://field/{type}/update-schema` for each field type before constructing payloads and set `payloadValidatedWithFieldSchemas=true`. `data` must be a JSON object mapping field keys to values (NOT an array), e.g. `{"title":"Hello","text":"..."}`. Pass the object directly (a JSON-encoded string is accepted for compatibility). It uses Kirby’s `$page->update($data, $language, $validate)` semantics. Recommended flow: call once with `confirm=false` to get a preview (`needsConfirm=true`, `updatedKeys`), then call again with `confirm=true` to actually write. Optional: `validate=true` to enforce blueprint rules; `language` to target a language. For field storage/payload guidance, see `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`. See `kirby://tool-examples` for copy-ready inputs. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Update Page Content',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function updatePageContent(
        string $id,
        #[Schema(
            type: 'object',
            description: 'JSON object mapping field keys to values (pass the object directly).',
            additionalProperties: true,
        )]
        array|string $data,
        bool $payloadValidatedWithFieldSchemas = false,
        bool $confirm = false,
        bool $validate = false,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $id = trim($id);
        if ($id === '') {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'id must not be empty.',
            ]);
        }

        if ($payloadValidatedWithFieldSchemas !== true) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'needsSchemaValidation' => true,
                'message' => 'Before updating, read kirby://blueprint/page/update-schema and kirby://field/{type}/update-schema for each field type involved, then retry with payloadValidatedWithFieldSchemas=true.',
                'schemaRefs' => [
                    'kirby://blueprints/update-schema',
                    'kirby://blueprint/page/update-schema',
                    'kirby://fields/update-schema',
                    'kirby://field/{type}/update-schema',
                ],
            ]);
        }

        if (is_string($data)) {
            $raw = trim($data);
            if ($raw === '') {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'data must be a non-empty JSON object (pass an object or a JSON string containing an object).',
                ]);
            }

            try {
                $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'Invalid JSON for data: ' . $exception->getMessage(),
                ]);
            }
        }

        if (!is_array($data)) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'data must be a JSON object mapping field keys to values.',
            ]);
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'Unable to encode data to JSON: ' . $exception->getMessage(),
            ]);
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
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Read the site content via the runtime CLI command `mcp:site:content`.
     *
     * @return array<string, mixed>|CallToolResult
     */
    #[McpToolIndex(
        whenToUse: 'Use to read the site’s current content via the installed runtime CLI command (safer than reading content files directly).',
        keywords: [
            'content' => 100,
            'read' => 80,
            'site' => 60,
            'fields' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_read_site_content',
        description: 'Read the site’s content (current version) via the installed `kirby mcp:site:content` CLI command. Requires kirby_runtime_install first. Resource: `kirby://site/content`.',
        annotations: new ToolAnnotations(
            title: 'Read Site Content',
            readOnlyHint: true,
            openWorldHint: false,
        ),
        meta: [
            'outputSchema' => self::READ_SITE_CONTENT_OUTPUT_SCHEMA,
        ],
    )]
    public function readSiteContent(
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $maxCharsPerField = max(0, $maxCharsPerField);

        $args = [
            RuntimeCommands::SITE_CONTENT,
            '--max=' . $maxCharsPerField,
        ];

        if (is_string($language) && trim($language) !== '') {
            $args[] = '--language=' . trim($language);
        }

        $result = $runner->runMarkedJson(RuntimeCommands::SITE_CONTENT_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Read a file's content/metadata via the runtime CLI command `mcp:file:content`.
     *
     * @return array<string, mixed>|CallToolResult
     */
    #[McpToolIndex(
        whenToUse: 'Use to read a file’s current content/metadata by id or uuid via the installed runtime CLI command.',
        keywords: [
            'content' => 90,
            'read' => 80,
            'file' => 70,
            'uuid' => 40,
            'metadata' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_read_file_content',
        description: 'Read a file’s content/metadata by id or uuid via the installed `kirby mcp:file:content` CLI command. Requires kirby_runtime_install first. Resource template: `kirby://file/content/{encodedIdOrUuid}`.',
        annotations: new ToolAnnotations(
            title: 'Read File Content',
            readOnlyHint: true,
            openWorldHint: false,
        ),
        meta: [
            'outputSchema' => self::READ_FILE_CONTENT_OUTPUT_SCHEMA,
        ],
    )]
    public function readFileContent(
        string $id,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $id = trim($id);
        if ($id === '') {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'id must not be empty.',
            ]);
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        $args = [
            RuntimeCommands::FILE_CONTENT,
            $id,
            '--max=' . $maxCharsPerField,
        ];

        if (is_string($language) && trim($language) !== '') {
            $args[] = '--language=' . trim($language);
        }

        $result = $runner->runMarkedJson(RuntimeCommands::FILE_CONTENT_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Read a user's content via the runtime CLI command `mcp:user:content`.
     *
     * @return array<string, mixed>|CallToolResult
     */
    #[McpToolIndex(
        whenToUse: 'Use to read a user’s current content by id or email via the installed runtime CLI command.',
        keywords: [
            'content' => 90,
            'read' => 80,
            'user' => 70,
            'email' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_read_user_content',
        description: 'Read a user’s content by id or email via the installed `kirby mcp:user:content` CLI command. Requires kirby_runtime_install first. Resource template: `kirby://user/content/{encodedIdOrEmail}`.',
        annotations: new ToolAnnotations(
            title: 'Read User Content',
            readOnlyHint: true,
            openWorldHint: false,
        ),
        meta: [
            'outputSchema' => self::READ_USER_CONTENT_OUTPUT_SCHEMA,
        ],
    )]
    public function readUserContent(
        string $id,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $id = trim($id);
        if ($id === '') {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'id must not be empty.',
            ]);
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        $args = [
            RuntimeCommands::USER_CONTENT,
            $id,
            '--max=' . $maxCharsPerField,
        ];

        if (is_string($language) && trim($language) !== '') {
            $args[] = '--language=' . trim($language);
        }

        $result = $runner->runMarkedJson(RuntimeCommands::USER_CONTENT_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Update the site content via the runtime CLI command `mcp:site:update`.
     *
     * @param array<string, mixed>|string $data
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to update site content via Kirby runtime, with explicit confirm=true guard.',
        keywords: [
            'update' => 100,
            'content' => 80,
            'write' => 70,
            'save' => 60,
            'site' => 60,
            'confirm' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_update_site_content',
        description: 'Update the site’s content via the installed `kirby mcp:site:update` CLI command. PREREQUISITE: Read `kirby://blueprint/site/update-schema` plus `kirby://field/{type}/update-schema` for each field type before constructing payloads and set `payloadValidatedWithFieldSchemas=true`. `data` must be a JSON object mapping field keys to values (NOT an array), e.g. `{"title":"Hello"}`. Pass the object directly (a JSON-encoded string is accepted for compatibility). It uses Kirby’s `$site->update($data, $language, $validate)` semantics. Recommended flow: call once with `confirm=false` to get a preview (`needsConfirm=true`, `updatedKeys`), then call again with `confirm=true` to actually write. Optional: `validate=true` to enforce blueprint rules; `language` to target a language. For field storage/payload guidance, see `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`. See `kirby://tool-examples` for copy-ready inputs. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Update Site Content',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function updateSiteContent(
        #[Schema(
            type: 'object',
            description: 'JSON object mapping field keys to values (pass the object directly).',
            additionalProperties: true,
        )]
        array|string $data,
        bool $payloadValidatedWithFieldSchemas = false,
        bool $confirm = false,
        bool $validate = false,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        if ($payloadValidatedWithFieldSchemas !== true) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'needsSchemaValidation' => true,
                'message' => 'Before updating, read kirby://blueprint/site/update-schema and kirby://field/{type}/update-schema for each field type involved, then retry with payloadValidatedWithFieldSchemas=true.',
                'schemaRefs' => [
                    'kirby://blueprints/update-schema',
                    'kirby://blueprint/site/update-schema',
                    'kirby://fields/update-schema',
                    'kirby://field/{type}/update-schema',
                ],
            ]);
        }

        if (is_string($data)) {
            $raw = trim($data);
            if ($raw === '') {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'data must be a non-empty JSON object (pass an object or a JSON string containing an object).',
                ]);
            }

            try {
                $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'Invalid JSON for data: ' . $exception->getMessage(),
                ]);
            }
        }

        if (!is_array($data)) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'data must be a JSON object mapping field keys to values.',
            ]);
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'Unable to encode data to JSON: ' . $exception->getMessage(),
            ]);
        }

        $args = [
            RuntimeCommands::SITE_UPDATE,
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

        $result = $runner->runMarkedJson(RuntimeCommands::SITE_UPDATE_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Update a file's content/metadata via the runtime CLI command `mcp:file:update`.
     *
     * @param array<string, mixed>|string $data
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to update file metadata (by id or uuid) via Kirby runtime, with explicit confirm=true guard.',
        keywords: [
            'update' => 100,
            'content' => 70,
            'metadata' => 80,
            'file' => 70,
            'write' => 60,
            'confirm' => 30,
            'uuid' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_update_file_content',
        description: 'Update a file’s content/metadata by id or uuid via the installed `kirby mcp:file:update` CLI command. PREREQUISITE: Read `kirby://blueprint/file/update-schema` plus `kirby://field/{type}/update-schema` for each field type before constructing payloads and set `payloadValidatedWithFieldSchemas=true`. `data` must be a JSON object mapping field keys to values (NOT an array), e.g. `{"alt":"Hello"}`. Pass the object directly (a JSON-encoded string is accepted for compatibility). It uses Kirby’s `$file->update($data, $language, $validate)` semantics. Recommended flow: call once with `confirm=false` to get a preview (`needsConfirm=true`, `updatedKeys`), then call again with `confirm=true` to actually write. Optional: `validate=true` to enforce blueprint rules; `language` to target a language. For field storage/payload guidance, see `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`. See `kirby://tool-examples` for copy-ready inputs. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Update File Content',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function updateFileContent(
        string $id,
        #[Schema(
            type: 'object',
            description: 'JSON object mapping field keys to values (pass the object directly).',
            additionalProperties: true,
        )]
        array|string $data,
        bool $payloadValidatedWithFieldSchemas = false,
        bool $confirm = false,
        bool $validate = false,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $id = trim($id);
        if ($id === '') {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'id must not be empty.',
            ]);
        }

        if ($payloadValidatedWithFieldSchemas !== true) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'needsSchemaValidation' => true,
                'message' => 'Before updating, read kirby://blueprint/file/update-schema and kirby://field/{type}/update-schema for each field type involved, then retry with payloadValidatedWithFieldSchemas=true.',
                'schemaRefs' => [
                    'kirby://blueprints/update-schema',
                    'kirby://blueprint/file/update-schema',
                    'kirby://fields/update-schema',
                    'kirby://field/{type}/update-schema',
                ],
            ]);
        }

        if (is_string($data)) {
            $raw = trim($data);
            if ($raw === '') {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'data must be a non-empty JSON object (pass an object or a JSON string containing an object).',
                ]);
            }

            try {
                $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'Invalid JSON for data: ' . $exception->getMessage(),
                ]);
            }
        }

        if (!is_array($data)) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'data must be a JSON object mapping field keys to values.',
            ]);
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'Unable to encode data to JSON: ' . $exception->getMessage(),
            ]);
        }

        $args = [
            RuntimeCommands::FILE_UPDATE,
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

        $result = $runner->runMarkedJson(RuntimeCommands::FILE_UPDATE_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
    }

    /**
     * Update a user's content via the runtime CLI command `mcp:user:update`.
     *
     * @param array<string, mixed>|string $data
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to update user content (by id or email) via Kirby runtime, with explicit confirm=true guard.',
        keywords: [
            'update' => 100,
            'content' => 80,
            'write' => 70,
            'user' => 70,
            'email' => 40,
            'confirm' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_update_user_content',
        description: 'Update a user’s content by id or email via the installed `kirby mcp:user:update` CLI command. PREREQUISITE: Read `kirby://blueprint/user/update-schema` plus `kirby://field/{type}/update-schema` for each field type before constructing payloads and set `payloadValidatedWithFieldSchemas=true`. `data` must be a JSON object mapping field keys to values (NOT an array), e.g. `{"city":"Berlin"}`. Pass the object directly (a JSON-encoded string is accepted for compatibility). It uses Kirby’s `$user->update($data, $language, $validate)` semantics. Recommended flow: call once with `confirm=false` to get a preview (`needsConfirm=true`, `updatedKeys`), then call again with `confirm=true` to actually write. Optional: `validate=true` to enforce blueprint rules; `language` to target a language. For field storage/payload guidance, see `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`. See `kirby://tool-examples` for copy-ready inputs. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Update User Content',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function updateUserContent(
        string $id,
        #[Schema(
            type: 'object',
            description: 'JSON object mapping field keys to values (pass the object directly).',
            additionalProperties: true,
        )]
        array|string $data,
        bool $payloadValidatedWithFieldSchemas = false,
        bool $confirm = false,
        bool $validate = false,
        ?string $language = null,
        int $maxCharsPerField = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $id = trim($id);
        if ($id === '') {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'id must not be empty.',
            ]);
        }

        if ($payloadValidatedWithFieldSchemas !== true) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'needsSchemaValidation' => true,
                'message' => 'Before updating, read kirby://blueprint/user/update-schema and kirby://field/{type}/update-schema for each field type involved, then retry with payloadValidatedWithFieldSchemas=true.',
                'schemaRefs' => [
                    'kirby://blueprints/update-schema',
                    'kirby://blueprint/user/update-schema',
                    'kirby://fields/update-schema',
                    'kirby://field/{type}/update-schema',
                ],
            ]);
        }

        if (is_string($data)) {
            $raw = trim($data);
            if ($raw === '') {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'data must be a non-empty JSON object (pass an object or a JSON string containing an object).',
                ]);
            }

            try {
                $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'message' => 'Invalid JSON for data: ' . $exception->getMessage(),
                ]);
            }
        }

        if (!is_array($data)) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'data must be a JSON object mapping field keys to values.',
            ]);
        }

        $maxCharsPerField = max(0, $maxCharsPerField);

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'message' => 'Unable to encode data to JSON: ' . $exception->getMessage(),
            ]);
        }

        $args = [
            RuntimeCommands::USER_UPDATE,
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

        $result = $runner->runMarkedJson(RuntimeCommands::USER_UPDATE_FILE, $args, timeoutSeconds: 60);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]));
        }

        $payload = array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);

        return $this->maybeStructuredResult($context, $payload);
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
            'php' => 30,
            '-r' => 70,
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
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $projectRoot = $this->context->projectRoot();
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $enabled = $this->isEvalEnabled($projectRoot);
        if ($enabled !== true) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'enabled' => false,
                'needsEnable' => true,
                'message' => 'Eval is disabled by default. Enable via env ' . self::ENV_ENABLE_EVAL . '=1 or via .kirby-mcp/mcp.json: {"eval":{"enabled":true}}.',
            ]);
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
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cliMeta' => $result->cliMeta(),
                'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                'cli' => $debug === true ? $result->cli() : null,
            ]));
        }

        /** @var array<string, mixed> $response */
        $response = $result->payload;

        if (isset($response['blueprints']) && is_array($response['blueprints'])) {
            foreach ($response['blueprints'] as $index => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                if (!is_array($entry['fieldSchemas'] ?? null)) {
                    $entry['fieldSchemas'] = is_array($entry['data'] ?? null)
                        ? FieldSchemaHelper::fromBlueprintData($entry['data'])
                        : [];
                }

                $response['blueprints'][$index] = $entry;
            }
        }
        $response['cliMeta'] = $result->cliMeta();

        if ($debug === true) {
            $response['cli'] = $result->cli();
        }

        return $this->maybeStructuredResult($context, $response);
    }

    /**
     * Evaluate a Kirby query language (dot-notation) string in Kirby runtime.
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Evaluate Kirby query language (dot-notation) strings to verify blueprint queries against runtime data. Enabled by default; requires confirm and can be disabled via config.',
        keywords: [
            'query' => 100,
            'dot' => 80,
            'dot-notation' => 60,
            'query-language' => 70,
            'blueprint' => 60,
            'options' => 40,
            'fetch' => 40,
            'evaluate' => 60,
            'runner' => 30,
            'ast' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_query_dot',
        description: 'Evaluate Kirby query language (dot-notation) strings in Kirby runtime via the installed `kirby mcp:query:dot` CLI command and return structured JSON. Enabled by default; disable via `.kirby-mcp/mcp.json` (`{\"query\":{\"enabled\":false}}`) and still requires confirm=true. Use `model` to set context (page id or UUID like `page://...`, `file://...`, `user://...`, user email, file path with extension, or `site`). See `kirby://glossary/query-language`. Requires kirby_runtime_install first.',
        annotations: new ToolAnnotations(
            title: 'Query (Dot Notation)',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function queryDot(
        string $query,
        ?string $model = null,
        bool $confirm = false,
        int $timeoutSeconds = 60,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        $projectRoot = $this->context->projectRoot();
        $runner = new RuntimeCommandRunner(new KirbyRuntimeContext($this->context));

        $enabled = $this->isQueryEnabled($projectRoot);
        if ($enabled !== true) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'enabled' => false,
                'needsEnable' => true,
                'message' => 'Query evaluation is disabled. Enable via env ' . self::ENV_ENABLE_QUERY . '=1 or via .kirby-mcp/mcp.json: {"query":{"enabled":true}}.',
            ]);
        }

        $args = [RuntimeCommands::QUERY_DOT, $query];

        if (is_string($model) && trim($model) !== '') {
            $args[] = '--model=' . trim($model);
        }

        if ($confirm === true) {
            $args[] = '--confirm';
        }

        if ($debug === true) {
            $args[] = '--debug';
        }

        $result = $runner->runMarkedJson(RuntimeCommands::QUERY_DOT_FILE, $args, timeoutSeconds: $timeoutSeconds);

        if ($result->installed !== true) {
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cliMeta' => $result->cliMeta(),
                'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                'cli' => $debug === true ? $result->cli() : null,
            ]));
        }

        /** @var array<string, mixed> $response */
        $response = $result->payload;
        $response['cliMeta'] = $result->cliMeta();

        if ($debug === true) {
            $response['cli'] = $result->cli();
        }

        return $this->maybeStructuredResult($context, $response);
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
        ?RequestContext $context = null,
    ): array|CallToolResult {
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
            return $this->maybeStructuredResult($context, $result->needsRuntimeInstallResponse());
        }

        if (!is_array($result->payload)) {
            return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                'cliMeta' => $result->cliMeta(),
                'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                'cli' => $debug === true ? $result->cli() : null,
            ]));
        }

        /** @var array<string, mixed> $response */
        $response = $result->payload;
        $response['cliMeta'] = $result->cliMeta();

        if ($debug === true) {
            $response['cli'] = $result->cli();
        }

        return $this->maybeStructuredResult($context, $response);
    }

    /**
     * @return array<int, string>
     */
    private function expectedCommandFiles(string $sourceRoot): array
    {
        $sourceRoot = rtrim($sourceRoot, DIRECTORY_SEPARATOR);
        if ($sourceRoot === '' || !is_dir($sourceRoot) || !is_readable($sourceRoot)) {
            return [];
        }

        $expected = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
        } catch (\Throwable) {
            return [];
        }

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

    private function isQueryEnabled(string $projectRoot): bool
    {
        $raw = getenv(self::ENV_ENABLE_QUERY);
        if (is_string($raw) && $raw !== '') {
            $normalized = strtolower(trim($raw));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return KirbyMcpConfig::load($projectRoot)->queryEnabled();
    }
}
