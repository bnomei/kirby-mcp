<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandResult;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Project\RootsCodeIndexer;
use Bnomei\KirbyMcp\Support\IndexList;
use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

final class CodeIndexTools
{
    use StructuredToolResult;

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to list available Kirby templates with file paths. Prefers runtime truth (includes plugin-registered templates) when runtime commands are installed.',
        keywords: [
            'template' => 100,
            'templates' => 100,
            'representation' => 50,
            'representations' => 50,
            'php' => 20,
            'index' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_templates_index',
        description: 'Index Kirby templates keyed by id (e.g. home, notes.json). Defaults to a compact payload (no raw CLI stdout/stderr). Prefers runtime `kirby mcp:templates` (includes plugin-registered templates); falls back to filesystem scan when runtime commands are not installed. Supports idsOnly, fields selection, filters, and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Templates Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function templatesIndex(
        bool $idsOnly = false,
        ?array $fields = null,
        ?string $activeSource = null,
        bool $overriddenOnly = false,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $runtime->roots();

            $templatesRoot = $roots->get('templates') ?? ($projectRoot . '/site/templates');

            $runtimeIndex = $this->runtimeIndexList(
                runtime: $runtime,
                rootPathFallback: $templatesRoot,
                expectedCommandRelativePath: RuntimeCommands::TEMPLATES_FILE,
                command: RuntimeCommands::TEMPLATES,
                listKey: 'templates',
                rootKey: 'templatesRoot',
                idsKey: 'templateIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                debug: $debug,
                augmentEntry: function (array $entry) use ($projectRoot): array {
                    $activeAbsolutePath = $entry['file']['active']['absolutePath'] ?? null;
                    if (is_string($activeAbsolutePath) && $activeAbsolutePath !== '') {
                        $entry['absolutePath'] = $activeAbsolutePath;
                        $entry['relativePath'] = $this->relativeToProject($projectRoot, $activeAbsolutePath);
                    } else {
                        $entry['absolutePath'] = null;
                        $entry['relativePath'] = null;
                    }

                    $rootRelativePath = $entry['file']['templatesRoot']['relativeToTemplatesRoot'] ?? null;
                    $entry['rootRelativePath'] = is_string($rootRelativePath) ? $rootRelativePath : null;

                    return $entry;
                },
            );

            if (($runtimeIndex['needsRuntimeInstall'] ?? false) !== true) {
                return $this->maybeStructuredResult($context, $runtimeIndex);
            }

            $data = (new RootsCodeIndexer())->templates($projectRoot, $roots);

            return $this->maybeStructuredResult($context, $this->filesystemIndexList(
                projectRoot: $projectRoot,
                host: $host,
                data: $data,
                rootKey: 'templatesRoot',
                rootFallback: $templatesRoot,
                listKey: 'templates',
                idsKey: 'templateIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                needsRuntimeInstall: true,
                message: 'Runtime CLI commands are not installed; only filesystem templates are indexed. Run kirby_runtime_install to include plugin-registered templates.',
                buildEntry: function (string $id, array $entry) use ($projectRoot): array {
                    $absolutePath = $entry['absolutePath'] ?? null;
                    $rootRelativePath = $entry['rootRelativePath'] ?? null;

                    return [
                        'id' => $id,
                        'name' => $entry['name'] ?? $id,
                        'representation' => $entry['representation'] ?? null,
                        'absolutePath' => $absolutePath,
                        'relativePath' => is_string($absolutePath) ? $this->relativeToProject($projectRoot, $absolutePath) : null,
                        'rootRelativePath' => is_string($rootRelativePath) ? $rootRelativePath : null,
                        'activeSource' => 'file',
                        'sources' => ['file'],
                        'overriddenByFile' => false,
                        'file' => [
                            'active' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                            ] : null,
                            'templatesRoot' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                                'relativeToTemplatesRoot' => is_string($rootRelativePath) ? $rootRelativePath : null,
                            ] : null,
                            'extension' => null,
                        ],
                    ];
                },
            ));
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_templates_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to list available Kirby snippets with file paths. Prefers runtime truth (includes plugin-registered snippets) when runtime commands are installed.',
        keywords: [
            'snippet' => 100,
            'snippets' => 100,
            'blocks' => 30,
            'include' => 20,
            'index' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_snippets_index',
        description: 'Index Kirby snippets keyed by id (e.g. blocks/gallery). Defaults to a compact payload (no raw CLI stdout/stderr). Prefers runtime `kirby mcp:snippets` (includes plugin-registered snippets); falls back to filesystem scan when runtime commands are not installed. Supports idsOnly, fields selection, filters, and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Snippets Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function snippetsIndex(
        bool $idsOnly = false,
        ?array $fields = null,
        ?string $activeSource = null,
        bool $overriddenOnly = false,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $runtime->roots();

            $snippetsRoot = $roots->get('snippets') ?? ($projectRoot . '/site/snippets');

            $runtimeIndex = $this->runtimeIndexList(
                runtime: $runtime,
                rootPathFallback: $snippetsRoot,
                expectedCommandRelativePath: RuntimeCommands::SNIPPETS_FILE,
                command: RuntimeCommands::SNIPPETS,
                listKey: 'snippets',
                rootKey: 'snippetsRoot',
                idsKey: 'snippetIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                debug: $debug,
                augmentEntry: function (array $entry) use ($projectRoot): array {
                    $activeAbsolutePath = $entry['file']['active']['absolutePath'] ?? null;
                    if (is_string($activeAbsolutePath) && $activeAbsolutePath !== '') {
                        $entry['absolutePath'] = $activeAbsolutePath;
                        $entry['relativePath'] = $this->relativeToProject($projectRoot, $activeAbsolutePath);
                    } else {
                        $entry['absolutePath'] = null;
                        $entry['relativePath'] = null;
                    }

                    $rootRelativePath = $entry['file']['snippetsRoot']['relativeToSnippetsRoot'] ?? null;
                    $entry['rootRelativePath'] = is_string($rootRelativePath) ? $rootRelativePath : null;

                    return $entry;
                },
            );

            if (($runtimeIndex['needsRuntimeInstall'] ?? false) !== true) {
                return $this->maybeStructuredResult($context, $runtimeIndex);
            }

            $data = (new RootsCodeIndexer())->snippets($projectRoot, $roots);

            return $this->maybeStructuredResult($context, $this->filesystemIndexList(
                projectRoot: $projectRoot,
                host: $host,
                data: $data,
                rootKey: 'snippetsRoot',
                rootFallback: $snippetsRoot,
                listKey: 'snippets',
                idsKey: 'snippetIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                needsRuntimeInstall: true,
                message: 'Runtime CLI commands are not installed; only filesystem snippets are indexed. Run kirby_runtime_install to include plugin-registered snippets.',
                buildEntry: function (string $id, array $entry) use ($projectRoot): array {
                    $absolutePath = $entry['absolutePath'] ?? null;
                    $rootRelativePath = $entry['rootRelativePath'] ?? null;

                    return [
                        'id' => $id,
                        'name' => $entry['name'] ?? $id,
                        'absolutePath' => $absolutePath,
                        'relativePath' => is_string($absolutePath) ? $this->relativeToProject($projectRoot, $absolutePath) : null,
                        'rootRelativePath' => is_string($rootRelativePath) ? $rootRelativePath : null,
                        'activeSource' => 'file',
                        'sources' => ['file'],
                        'overriddenByFile' => false,
                        'file' => [
                            'active' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                            ] : null,
                            'snippetsRoot' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                                'relativeToSnippetsRoot' => is_string($rootRelativePath) ? $rootRelativePath : null,
                            ] : null,
                            'extension' => null,
                        ],
                    ];
                },
            ));
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_snippets_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to list available Kirby named collections with file paths. Prefers runtime truth (includes plugin-registered collections) when runtime commands are installed.',
        keywords: [
            'collection' => 100,
            'collections' => 100,
            'named' => 80,
            'query' => 20,
            'reuse' => 20,
            'index' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_collections_index',
        description: 'Index Kirby named collections keyed by id (e.g. articles/latest). Defaults to a compact payload (no raw CLI stdout/stderr). Prefers runtime `kirby mcp:collections` (includes plugin-registered collections); falls back to filesystem scan when runtime commands are not installed. Supports idsOnly, fields selection, filters, and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Collections Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function collectionsIndex(
        bool $idsOnly = false,
        ?array $fields = null,
        ?string $activeSource = null,
        bool $overriddenOnly = false,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $runtime->roots();

            $collectionsRoot = $roots->get('collections') ?? ($projectRoot . '/site/collections');

            $runtimeIndex = $this->runtimeIndexList(
                runtime: $runtime,
                rootPathFallback: $collectionsRoot,
                expectedCommandRelativePath: RuntimeCommands::COLLECTIONS_FILE,
                command: RuntimeCommands::COLLECTIONS,
                listKey: 'collections',
                rootKey: 'collectionsRoot',
                idsKey: 'collectionIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                debug: $debug,
                augmentEntry: function (array $entry) use ($projectRoot): array {
                    $activeAbsolutePath = $entry['file']['active']['absolutePath'] ?? null;
                    if (is_string($activeAbsolutePath) && $activeAbsolutePath !== '') {
                        $entry['absolutePath'] = $activeAbsolutePath;
                        $entry['relativePath'] = $this->relativeToProject($projectRoot, $activeAbsolutePath);
                    } else {
                        $entry['absolutePath'] = null;
                        $entry['relativePath'] = null;
                    }

                    $rootRelativePath = $entry['file']['collectionsRoot']['relativeToCollectionsRoot'] ?? null;
                    $entry['rootRelativePath'] = is_string($rootRelativePath) ? $rootRelativePath : null;

                    return $entry;
                },
            );

            if (($runtimeIndex['needsRuntimeInstall'] ?? false) !== true) {
                return $this->maybeStructuredResult($context, $runtimeIndex);
            }

            $data = (new RootsCodeIndexer())->collections($projectRoot, $roots);

            return $this->maybeStructuredResult($context, $this->filesystemIndexList(
                projectRoot: $projectRoot,
                host: $host,
                data: $data,
                rootKey: 'collectionsRoot',
                rootFallback: $collectionsRoot,
                listKey: 'collections',
                idsKey: 'collectionIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                needsRuntimeInstall: true,
                message: 'Runtime CLI commands are not installed; only filesystem collections are indexed. Run kirby_runtime_install to include plugin-registered collections.',
                buildEntry: function (string $id, array $entry) use ($projectRoot): array {
                    $absolutePath = $entry['absolutePath'] ?? null;
                    $rootRelativePath = $entry['rootRelativePath'] ?? null;

                    return [
                        'id' => $id,
                        'name' => $entry['name'] ?? $id,
                        'absolutePath' => $absolutePath,
                        'relativePath' => is_string($absolutePath) ? $this->relativeToProject($projectRoot, $absolutePath) : null,
                        'rootRelativePath' => is_string($rootRelativePath) ? $rootRelativePath : null,
                        'activeSource' => 'file',
                        'sources' => ['file'],
                        'overriddenByFile' => false,
                        'file' => [
                            'active' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                            ] : null,
                            'collectionsRoot' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                                'relativeToCollectionsRoot' => is_string($rootRelativePath) ? $rootRelativePath : null,
                            ] : null,
                            'extension' => null,
                        ],
                    ];
                },
            ));
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_collections_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to list available Kirby controllers with file paths. Prefers runtime truth (includes plugin-registered controllers) when runtime commands are installed.',
        keywords: [
            'controller' => 100,
            'controllers' => 100,
            'index' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_controllers_index',
        description: 'Index Kirby controllers keyed by id (e.g. album, album.json). Defaults to a compact payload (no raw CLI stdout/stderr). Prefers runtime `kirby mcp:controllers` (includes plugin-registered controllers); falls back to filesystem scan when runtime commands are not installed. Supports idsOnly, fields selection, filters, and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Controllers Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function controllersIndex(
        bool $idsOnly = false,
        ?array $fields = null,
        ?string $activeSource = null,
        bool $overriddenOnly = false,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $runtime->roots();

            $controllersRoot = $roots->get('controllers') ?? ($projectRoot . '/site/controllers');

            $runtimeIndex = $this->runtimeIndexList(
                runtime: $runtime,
                rootPathFallback: $controllersRoot,
                expectedCommandRelativePath: RuntimeCommands::CONTROLLERS_FILE,
                command: RuntimeCommands::CONTROLLERS,
                listKey: 'controllers',
                rootKey: 'controllersRoot',
                idsKey: 'controllerIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                debug: $debug,
                augmentEntry: function (array $entry) use ($projectRoot): array {
                    $activeAbsolutePath = $entry['file']['active']['absolutePath'] ?? null;
                    if (is_string($activeAbsolutePath) && $activeAbsolutePath !== '') {
                        $entry['absolutePath'] = $activeAbsolutePath;
                        $entry['relativePath'] = $this->relativeToProject($projectRoot, $activeAbsolutePath);
                    } else {
                        $entry['absolutePath'] = null;
                        $entry['relativePath'] = null;
                    }

                    $rootRelativePath = $entry['file']['controllersRoot']['relativeToControllersRoot'] ?? null;
                    $entry['rootRelativePath'] = is_string($rootRelativePath) ? $rootRelativePath : null;

                    return $entry;
                },
            );

            if (($runtimeIndex['needsRuntimeInstall'] ?? false) !== true) {
                return $this->maybeStructuredResult($context, $runtimeIndex);
            }

            $data = (new RootsCodeIndexer())->controllers($projectRoot, $roots);

            return $this->maybeStructuredResult($context, $this->filesystemIndexList(
                projectRoot: $projectRoot,
                host: $host,
                data: $data,
                rootKey: 'controllersRoot',
                rootFallback: $controllersRoot,
                listKey: 'controllers',
                idsKey: 'controllerIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: $activeSource,
                overriddenOnly: $overriddenOnly,
                limit: $limit,
                cursor: $cursor,
                needsRuntimeInstall: true,
                message: 'Runtime CLI commands are not installed; only filesystem controllers are indexed. Run kirby_runtime_install to include plugin-registered controllers.',
                buildEntry: function (string $id, array $entry) use ($projectRoot): array {
                    $absolutePath = $entry['absolutePath'] ?? null;
                    $rootRelativePath = $entry['rootRelativePath'] ?? null;

                    return [
                        'id' => $id,
                        'name' => $entry['name'] ?? $id,
                        'representation' => $entry['representation'] ?? null,
                        'absolutePath' => $absolutePath,
                        'relativePath' => is_string($absolutePath) ? $this->relativeToProject($projectRoot, $absolutePath) : null,
                        'rootRelativePath' => is_string($rootRelativePath) ? $rootRelativePath : null,
                        'activeSource' => 'file',
                        'sources' => ['file'],
                        'overriddenByFile' => false,
                        'file' => [
                            'active' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                            ] : null,
                            'controllersRoot' => is_string($absolutePath) ? [
                                'absolutePath' => $absolutePath,
                                'relativeToControllersRoot' => is_string($rootRelativePath) ? $rootRelativePath : null,
                            ] : null,
                            'extension' => null,
                        ],
                    ];
                },
            ));
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_controllers_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   host: string|null,
     *   modelsRoot: string,
     *   exists: bool,
     *   models: array<string, array{
     *     id: string,
     *     name: string,
     *     representation: string|null,
     *     absolutePath: string,
     *     relativePath: string,
     *     rootRelativePath: string
     *   }>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to list registered Kirby page models (id → class) with file paths. Prefers runtime truth via installed runtime CLI commands; falls back to filesystem scan when runtime commands are not installed.',
        keywords: [
            'model' => 100,
            'models' => 100,
            'page' => 20,
            'index' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_models_index',
        description: 'Index registered Kirby page models keyed by id (e.g. default, article) with class + file path info. Prefers runtime `kirby mcp:models`; falls back to filesystem scan when runtime commands are not installed. Supports idsOnly, fields selection and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Models Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function modelsIndex(
        bool $idsOnly = false,
        ?array $fields = null,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $runtime->roots();

            $modelsRoot = $roots->get('models') ?? ($projectRoot . '/site/models');

            $runtimeIndex = $this->runtimeIndexList(
                runtime: $runtime,
                rootPathFallback: $modelsRoot,
                expectedCommandRelativePath: RuntimeCommands::MODELS_FILE,
                command: RuntimeCommands::MODELS,
                listKey: 'models',
                rootKey: 'modelsRoot',
                idsKey: 'modelIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: null,
                overriddenOnly: false,
                limit: $limit,
                cursor: $cursor,
                debug: $debug,
                augmentEntry: function (array $entry) use ($projectRoot): array {
                    $activeAbsolutePath = $entry['file']['active']['absolutePath'] ?? null;
                    if (is_string($activeAbsolutePath) && $activeAbsolutePath !== '') {
                        $entry['absolutePath'] = $activeAbsolutePath;
                        $entry['relativePath'] = $this->relativeToProject($projectRoot, $activeAbsolutePath);
                    } else {
                        $entry['absolutePath'] = null;
                        $entry['relativePath'] = null;
                    }

                    $rootRelativePath = $entry['file']['modelsRoot']['relativeToModelsRoot'] ?? null;
                    $entry['rootRelativePath'] = is_string($rootRelativePath) ? $rootRelativePath : null;

                    return $entry;
                },
            );

            if (($runtimeIndex['needsRuntimeInstall'] ?? false) !== true) {
                return $this->maybeStructuredResult($context, $runtimeIndex);
            }

            $data = (new RootsCodeIndexer())->models($projectRoot, $roots);

            return $this->maybeStructuredResult($context, $this->filesystemIndexList(
                projectRoot: $projectRoot,
                host: $host,
                data: $data,
                rootKey: 'modelsRoot',
                rootFallback: $data['modelsRoot'] ?? ($projectRoot . '/site/models'),
                listKey: 'models',
                idsKey: 'modelIds',
                idsOnly: $idsOnly,
                fields: $fields,
                limit: $limit,
                cursor: $cursor,
                needsRuntimeInstall: true,
                message: 'Runtime CLI commands are not installed; only filesystem model files are indexed. Run kirby_runtime_install to list only registered models and include plugin-registered ones.',
                buildEntry: static function (string $id, array $entry): array {
                    $entry['id'] = $id;
                    return $entry;
                },
            ));
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_models_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   host: string|null,
     *   pluginsRoot: string,
     *   exists: bool,
     *   plugins: array<string, array{
     *     id: string,
     *     dirName: string,
     *     absolutePath: string,
     *     relativePath: string,
     *     hasIndexPhp: bool,
     *     hasComposerJson: bool,
     *     hasPackageJson: bool,
     *     hasBlueprints: bool,
     *     hasSnippets: bool,
     *     hasTemplates: bool,
     *     hasControllers: bool,
     *     hasModels: bool,
     *     hasCommands: bool
     *   }>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to list loaded Kirby plugins (runtime truth) and what capabilities they provide (extensions + common folders). Falls back to filesystem scan when runtime commands are not installed.',
        keywords: [
            'plugin' => 100,
            'plugins' => 100,
            'loaded' => 40,
            'runtime' => 40,
            'extensions' => 40,
            'commands' => 30,
            'blueprints' => 20,
            'snippets' => 20,
            'templates' => 20,
            'controllers' => 20,
            'models' => 20,
            'index' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_plugins_index',
        description: 'Index loaded Kirby plugins keyed by id (runtime truth) via `kirby mcp:plugins` and enrich with common folder hints. Falls back to filesystem scan of roots.plugins (may include inactive plugins). Supports idsOnly, fields selection and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Plugins Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function pluginsIndex(
        bool $idsOnly = false,
        ?array $fields = null,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $runtime->roots();

            $pluginsRoot = $roots->get('plugins') ?? ($projectRoot . '/site/plugins');

            $runtimeIndex = $this->runtimeIndexList(
                runtime: $runtime,
                rootPathFallback: $pluginsRoot,
                expectedCommandRelativePath: RuntimeCommands::PLUGINS_FILE,
                command: RuntimeCommands::PLUGINS,
                listKey: 'plugins',
                rootKey: 'pluginsRoot',
                idsKey: 'pluginIds',
                idsOnly: $idsOnly,
                fields: $fields,
                activeSource: null,
                overriddenOnly: false,
                limit: $limit,
                cursor: $cursor,
                debug: $debug,
                augmentEntry: function (array $entry) use ($projectRoot): array {
                    $activeAbsolutePath = $entry['file']['active']['absolutePath'] ?? null;
                    if (is_string($activeAbsolutePath) && $activeAbsolutePath !== '') {
                        $entry['absolutePath'] = $activeAbsolutePath;
                        $entry['relativePath'] = $this->relativeToProject($projectRoot, $activeAbsolutePath);
                    } else {
                        $entry['absolutePath'] = null;
                        $entry['relativePath'] = null;
                    }

                    $rootRelativePath = $entry['file']['pluginsRoot']['relativeToPluginsRoot'] ?? null;
                    $entry['rootRelativePath'] = is_string($rootRelativePath) ? $rootRelativePath : null;

                    return $entry;
                },
            );

            if (($runtimeIndex['needsRuntimeInstall'] ?? false) !== true) {
                return $this->maybeStructuredResult($context, $runtimeIndex);
            }

            $data = (new RootsCodeIndexer())->plugins($projectRoot, $roots);

            return $this->maybeStructuredResult($context, $this->filesystemIndexList(
                projectRoot: $projectRoot,
                host: $host,
                data: $data,
                rootKey: 'pluginsRoot',
                rootFallback: $data['pluginsRoot'] ?? ($projectRoot . '/site/plugins'),
                listKey: 'plugins',
                idsKey: 'pluginIds',
                idsOnly: $idsOnly,
                fields: $fields,
                limit: $limit,
                cursor: $cursor,
                needsRuntimeInstall: true,
                message: 'Runtime CLI commands are not installed; only filesystem plugin folders are indexed (may include inactive plugins). Run kirby_runtime_install to list loaded plugins from Kirby runtime.',
                buildEntry: static function (string $id, array $entry): array {
                    $entry['id'] = $id;
                    return $entry;
                },
            ));
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_plugins_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @param array<int, string>|null $fields
     * @param callable(array<string, mixed>): array<string, mixed> $augmentEntry
     * @return array<string, mixed>
     */
    private function runtimeIndexList(
        KirbyRuntimeContext $runtime,
        string $rootPathFallback,
        string $expectedCommandRelativePath,
        string $command,
        string $listKey,
        string $rootKey,
        string $idsKey,
        bool $idsOnly,
        ?array $fields,
        ?string $activeSource,
        bool $overriddenOnly,
        int $limit,
        int $cursor,
        bool $debug,
        callable $augmentEntry,
    ): array {
        $projectRoot = $runtime->projectRoot();
        $host = $runtime->host();

        $args = [$command];
        if ($idsOnly === true) {
            $args[] = '--ids-only';
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

        $result = (new RuntimeCommandRunner($runtime))->runMarkedJson(
            expectedCommandRelativePath: $expectedCommandRelativePath,
            args: $args,
            timeoutSeconds: 60,
        );

        if ($result->installed !== true) {
            return $result->needsRuntimeInstallResponse();
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'mode' => 'runtime',
                'projectRoot' => $projectRoot,
                'host' => $host,
                $rootKey => $rootPathFallback,
                'cliMeta' => $result->cliMeta(),
                'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                'cli' => $debug === true ? $result->cli() : null,
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $result->payload;

        $list = $payload[$listKey] ?? null;
        $ids = [];
        $byId = [];

        if (is_array($list)) {
            foreach ($list as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $id = $entry['id'] ?? null;
                if (!is_string($id) || $id === '') {
                    continue;
                }

                $ids[] = $id;

                if ($idsOnly === true) {
                    continue;
                }

                $entry = $augmentEntry($entry);
                $byId[$id] = IndexList::selectFields($entry, $fields, $id);
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        if ($idsOnly === false) {
            ksort($byId);
        }

        $resolvedRoot = $payload[$rootKey] ?? $rootPathFallback;
        $exists = is_string($resolvedRoot) ? is_dir($resolvedRoot) : false;

        /** @var array<string, mixed> $payload */
        $response = [
            'ok' => $payload['ok'] ?? true,
            'mode' => 'runtime',
            'projectRoot' => $projectRoot,
            'host' => $host,
            $rootKey => $resolvedRoot,
            'exists' => $exists,
            'counts' => $payload['counts'] ?? null,
            'filters' => $payload['filters'] ?? null,
            'pagination' => $payload['pagination'] ?? null,
            'cliMeta' => $result->cliMeta(),
        ];

        if ($idsOnly === true) {
            $response[$idsKey] = $ids;
        } else {
            $response[$listKey] = $byId;
        }

        if ($debug === true) {
            $response['cli'] = $result->cliResult?->toArray();
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string>|null $fields
     * @param callable(string, array<string, mixed>): array<string, mixed> $buildEntry
     * @return array<string, mixed>
     */
    private function filesystemIndexList(
        string $projectRoot,
        ?string $host,
        array $data,
        string $rootKey,
        string $rootFallback,
        string $listKey,
        string $idsKey,
        bool $idsOnly,
        ?array $fields,
        callable $buildEntry,
        ?string $activeSource = null,
        bool $overriddenOnly = false,
        int $limit = 0,
        int $cursor = 0,
        bool $needsRuntimeInstall = false,
        ?string $message = null,
    ): array {
        $root = $data[$rootKey] ?? $rootFallback;
        $root = is_string($root) ? $root : $rootFallback;

        $exists = $data['exists'] ?? null;
        $exists = is_bool($exists) ? $exists : is_dir($root);

        $items = $data[$listKey] ?? [];
        $items = is_array($items) ? $items : [];

        $activeSourceFilter = is_string($activeSource) ? strtolower(trim($activeSource)) : null;
        if ($activeSourceFilter === '') {
            $activeSourceFilter = null;
        }
        if ($activeSourceFilter !== null && $activeSourceFilter !== 'file' && $activeSourceFilter !== 'extension') {
            $activeSourceFilter = null;
        }

        $ids = array_values(array_filter(array_keys($items), static fn ($id) => is_string($id) && $id !== ''));
        sort($ids);

        $unfilteredTotal = count($ids);

        if ($activeSourceFilter === 'extension') {
            $ids = [];
        }

        if ($overriddenOnly === true) {
            $ids = [];
        }

        $filteredTotal = count($ids);

        $pagination = IndexList::paginateIds($ids, $cursor, $limit);
        $pagedIds = $pagination['ids'];
        $paginationMeta = $pagination['pagination'];

        $byId = [];
        if ($idsOnly === false) {
            foreach ($pagedIds as $id) {
                $entry = $items[$id] ?? null;
                if (!is_array($entry)) {
                    continue;
                }

                $built = $buildEntry($id, $entry);
                $byId[$id] = IndexList::selectFields($built, $fields, $id);
            }

            ksort($byId);
        }

        $filters = [];
        if ($activeSourceFilter !== null) {
            /** @var 'file'|'extension' $activeSourceFilter */
            $filters['activeSource'] = $activeSourceFilter;
        }
        if ($overriddenOnly === true) {
            $filters['overriddenOnly'] = true;
        }

        $response = [
            'ok' => true,
            'mode' => 'filesystem',
            'projectRoot' => $projectRoot,
            'host' => $host,
            $rootKey => $root,
            'exists' => $exists,
            'filters' => $filters,
            'pagination' => $paginationMeta,
            'counts' => [
                'extensions' => 0,
                'files' => $unfilteredTotal,
                'total' => $unfilteredTotal,
                'filtered' => $filteredTotal,
                'returned' => $paginationMeta['returned'],
                'overriddenByFile' => 0,
            ],
        ];

        if ($needsRuntimeInstall === true) {
            $response['needsRuntimeInstall'] = true;
        }

        if (is_string($message) && $message !== '') {
            $response['message'] = $message;
        }

        if ($idsOnly === true) {
            $response[$idsKey] = $pagedIds;
        } else {
            $response[$listKey] = $byId;
        }

        return $response;
    }

    private function relativeToProject(string $projectRoot, string $absolutePath): string
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR);

        if ($projectRoot !== '' && str_starts_with($absolutePath, $projectRoot . DIRECTORY_SEPARATOR)) {
            return ltrim(substr($absolutePath, strlen($projectRoot)), DIRECTORY_SEPARATOR);
        }

        return $absolutePath;
    }
}
