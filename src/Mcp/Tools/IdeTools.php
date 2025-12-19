<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Blueprint\BlueprintScanner;
use Bnomei\KirbyMcp\Blueprint\BlueprintType;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Project\KirbyRoots;
use Bnomei\KirbyMcp\Project\RootsCodeIndexer;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class IdeTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Generate IDE helper files (optional, regeneratable).
     *
     * @return array{
     *   ok: bool,
     *   dryRun: bool,
     *   projectRoot: string,
     *   host: string|null,
     *   outputDir: string,
     *   source: 'runtime'|'filesystem',
     *   watchedInputs: array<int, string>,
     *   inputs: array{latestMtime: int|null, latestPath: string|null},
     *   sources: array{blueprints:'runtime'|'filesystem', snippets:'runtime'|'filesystem'},
     *   files: array<int, array{
     *     id: string,
     *     path: string,
     *     action: 'create'|'overwrite'|'skip',
     *     reason: string|null,
     *     bytes: int|null,
     *     ok: bool,
     *     error: string|null
     *   }>,
     *   stats: array{
     *     blueprints: int,
     *     fields: array{page:int, file:int, user:int, site:int},
     *     collisions: array{page:int, file:int, user:int, site:int},
     *     skippedInvalid: int
     *   },
     *   notes: array<int, string>,
     *   errors: array<int, array{message:string, context?:array<string,mixed>}>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to generate regeneratable IDE helper files from blueprints and indexes (for humans). Prefer calling kirby_ide_helpers_status after blueprint/config changes to decide if regeneration is needed.',
        keywords: [
            'ide' => 100,
            'helpers' => 90,
            'generate' => 80,
            'types' => 60,
            'phpdoc' => 40,
            'phpstorm' => 40,
            'intelephense' => 40,
            'blueprints' => 50,
            'fields' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_generate_ide_helpers',
        description: 'Generate IDE helper files (optional/regeneratable) from project context (blueprints, indexes). Writes to `.kirby-mcp/` by default. Use `kirby_ide_helpers_status` to check freshness and missing PHPDoc hints.',
        annotations: new ToolAnnotations(
            title: 'Generate IDE Helpers',
            readOnlyHint: false,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        ),
    )]
    public function generateIdeHelpers(
        ?ClientGateway $client = null,
        bool $dryRun = true,
        bool $force = false,
        bool $preferRuntime = true,
    ): array {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();

            $outputDir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.kirby-mcp';

            $roots = $this->rootsOrFallback($runtime, $projectRoot);
            $watchedInputs = $this->watchedInputs($projectRoot, $roots);
            [$latestInputMtime, $latestInputPath] = $this->latestMtimeAcross($watchedInputs);

            $errors = [];

            $blueprintSource = 'filesystem';
            $blueprints = [];

            if ($preferRuntime === true) {
                $runner = new RuntimeCommandRunner($runtime);
                $args = [
                    RuntimeCommands::BLUEPRINTS,
                    '--with-data',
                    '--type=pages,files,users,site',
                ];

                $result = $runner->runMarkedJson(
                    expectedCommandRelativePath: RuntimeCommands::BLUEPRINTS_FILE,
                    args: $args,
                    timeoutSeconds: 120,
                );

                if ($result->installed === true && is_array($result->payload)) {
                    $blueprintSource = 'runtime';
                    $blueprints = $this->blueprintsFromRuntimePayload($result->payload);
                } elseif ($result->installed === true) {
                    $errors[] = [
                        'message' => 'Runtime blueprints command returned an invalid payload; falling back to filesystem scan.',
                        'context' => [
                            'cliMeta' => $result->cliMeta(),
                        ],
                    ];
                }
            }

            if ($blueprints === []) {
                $blueprintsRoot = $roots->get('blueprints') ?? ($projectRoot . '/site/blueprints');
                $scan = (new BlueprintScanner())->scan($projectRoot, $blueprintsRoot);

                foreach ($scan->errors as $error) {
                    $errors[] = [
                        'message' => $error['error'],
                        'context' => [
                            'path' => $error['path'],
                        ],
                    ];
                }

                foreach ($scan->blueprints as $id => $file) {
                    if (!is_array($file->data)) {
                        continue;
                    }

                    $blueprints[$id] = [
                        'id' => $id,
                        'type' => $this->blueprintTypeFromScanner($file->type),
                        'data' => $file->data,
                    ];
                }
            }

            $targets = [
                'pages' => ['class' => 'Kirby\\Cms\\Page'],
                'files' => ['class' => 'Kirby\\Cms\\File'],
                'users' => ['class' => 'Kirby\\Cms\\User'],
                'site' => ['class' => 'Kirby\\Cms\\Site'],
            ];

            $methods = [
                'pages' => [],
                'files' => [],
                'users' => [],
                'site' => [],
            ];

            $collisions = [
                'pages' => [],
                'files' => [],
                'users' => [],
                'site' => [],
            ];

            $skippedInvalid = 0;

            foreach ($blueprints as $id => $blueprint) {
                $type = $blueprint['type'] ?? null;
                if (!is_string($type) || !isset($targets[$type])) {
                    continue;
                }

                $data = $blueprint['data'] ?? null;
                if (!is_array($data)) {
                    continue;
                }

                $fieldKeys = $this->extractFieldKeysFromBlueprint($data);
                foreach ($fieldKeys as $fieldKey) {
                    $methodName = $this->normalizeFieldMethodName($fieldKey);
                    if ($methodName === null) {
                        $skippedInvalid++;
                        continue;
                    }

                    $targetClass = $targets[$type]['class'];
                    if ($this->collidesWithRealMethod($targetClass, $methodName) === true) {
                        $collisions[$type][$methodName][$id] = true;
                        continue;
                    }

                    $methods[$type][$methodName][$id] = true;
                }
            }

            $rendered = $this->renderKirbyFieldsStub($methods);

            $files = [];
            $files[] = $this->writePlannedFile(
                id: 'kirby-mcp:_ide_helper_kirby_fields',
                path: $outputDir . DIRECTORY_SEPARATOR . '_ide_helper_kirby_fields.php',
                contents: $rendered,
                dryRun: $dryRun,
                force: $force,
            );

            $snippetsSource = 'filesystem';
            $snippets = null;

            if ($preferRuntime === true) {
                $runner = new RuntimeCommandRunner($runtime);
                $args = [
                    RuntimeCommands::SNIPPETS,
                    '--ids-only',
                ];

                $result = $runner->runMarkedJson(
                    expectedCommandRelativePath: RuntimeCommands::SNIPPETS_FILE,
                    args: $args,
                    timeoutSeconds: 60,
                );

                if ($result->installed === true && is_array($result->payload)) {
                    $items = $result->payload['snippets'] ?? null;
                    if (is_array($items)) {
                        $snippetsSource = 'runtime';
                        $snippets = [];
                        foreach ($items as $item) {
                            if (!is_array($item)) {
                                continue;
                            }
                            $id = $item['id'] ?? null;
                            if (is_string($id) && trim($id) !== '') {
                                $snippets[] = trim($id);
                            }
                        }
                    } else {
                        $errors[] = [
                            'message' => 'Runtime snippets command returned an invalid payload; falling back to filesystem scan.',
                            'context' => [
                                'cliMeta' => $result->cliMeta(),
                            ],
                        ];
                    }
                } elseif ($result->installed === true) {
                    $errors[] = [
                        'message' => 'Runtime snippets command returned an invalid payload; falling back to filesystem scan.',
                        'context' => [
                            'cliMeta' => $result->cliMeta(),
                        ],
                    ];
                }
            }

            if ($snippets === null) {
                $snippetsIndex = (new RootsCodeIndexer())->snippets($projectRoot, $roots);
                $snippets = array_values(array_keys(is_array($snippetsIndex['snippets'] ?? null) ? $snippetsIndex['snippets'] : []));
            }

            $phpstormMeta = $this->renderPhpStormMeta(
                snippets: $snippets,
            );

            $files[] = $this->writePlannedFile(
                id: 'kirby-mcp:phpstorm-meta',
                path: $outputDir . DIRECTORY_SEPARATOR . '.phpstorm.meta.php' . DIRECTORY_SEPARATOR . 'kirby.php',
                contents: $phpstormMeta,
                dryRun: $dryRun,
                force: $force,
            );

            $indexJson = $this->renderBlueprintIndexJson(
                projectRoot: $projectRoot,
                host: $host,
                source: $blueprintSource,
                methods: $methods,
                collisions: $collisions,
            );

            $files[] = $this->writePlannedFile(
                id: 'kirby-mcp:blueprints-index',
                path: $outputDir . DIRECTORY_SEPARATOR . 'kirby-blueprints.index.json',
                contents: $indexJson,
                dryRun: $dryRun,
                force: $force,
            );

            $stats = [
                'blueprints' => count($blueprints),
                'fields' => [
                    'page' => count($methods['pages']),
                    'file' => count($methods['files']),
                    'user' => count($methods['users']),
                    'site' => count($methods['site']),
                ],
                'collisions' => [
                    'page' => count($collisions['pages']),
                    'file' => count($collisions['files']),
                    'user' => count($collisions['users']),
                    'site' => count($collisions['site']),
                ],
                'skippedInvalid' => $skippedInvalid,
            ];

            $notes = [
                'Generated files are for IDE parsing only. Do not include/require them at runtime.',
                'After changing blueprints/snippets/config, run kirby_ide_helpers_status to see if helpers are stale (mtime-based).',
            ];

            return [
                'ok' => array_reduce($files, static fn (bool $ok, array $file): bool => $ok && ($file['ok'] ?? false) === true, true),
                'dryRun' => $dryRun,
                'projectRoot' => $projectRoot,
                'host' => $host,
                'outputDir' => $outputDir,
                'source' => $blueprintSource,
                'watchedInputs' => $watchedInputs,
                'inputs' => [
                    'latestMtime' => $latestInputMtime,
                    'latestPath' => $latestInputPath,
                ],
                'sources' => [
                    'blueprints' => $blueprintSource,
                    'snippets' => $snippetsSource,
                ],
                'files' => $files,
                'stats' => $stats,
                'notes' => $notes,
                'errors' => $errors,
            ];
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_generate_ide_helpers',
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
     *   watchedInputs: array<int, string>,
     *   inputs: array{latestMtime: int|null, latestPath: string|null},
     *   helpers: array<int, array{
     *     id: string,
     *     path: string,
     *     exists: bool,
     *     mtime: int|null,
     *     stale: bool|null,
     *     reason: string|null
     *   }>,
     *   templates: array{
     *     total: int,
     *     withKirbyVarHints: int,
     *     missingKirbyVarHints: int,
     *     missing: array<int, array{id: string, relativePath: string|null, absolutePath: string|null}>
     *   },
     *   snippets: array{
     *     total: int,
     *     withKirbyVarHints: int,
     *     missingKirbyVarHints: int,
     *     missing: array<int, array{id: string, relativePath: string|null, absolutePath: string|null}>
     *   },
     *   controllers: array{
     *     total: int,
     *     closureControllers: int,
     *     withKirbyTypeHints: int,
     *     missingKirbyTypeHints: int,
     *     missing: array<int, array{id: string, relativePath: string|null, absolutePath: string|null}>
     *   },
     *   pageModels: array{
     *     total: int,
     *     pageModelFiles: int,
     *     withKirbyTypeHints: int,
     *     missingKirbyTypeHints: int,
     *     missing: array<int, array{id: string, relativePath: string|null, absolutePath: string|null}>
     *   },
     *   recommendations: array<int, string>,
     *   notes: array<int, string>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to get a quick IDE/DX status: missing template/snippet @var hints and whether generated helper files look stale. Called by kirby_init for freshness.',
        keywords: [
            'ide' => 100,
            'dx' => 80,
            'status' => 100,
            'helpers' => 80,
            'types' => 40,
            'phpstorm' => 40,
            'intelephense' => 40,
            'phpdoc' => 60,
            'hints' => 60,
            'var' => 40,
            'stale' => 50,
            'freshness' => 50,
        ],
    )]
    #[McpTool(
        name: 'kirby_ide_helpers_status',
        description: 'Report IDE helper status (missing template/snippet PHPDoc @var hints + helper file freshness using mtimes). Designed to keep LLMs tool-first and avoid stale static helpers.',
        annotations: new ToolAnnotations(
            title: 'IDE Helpers Status',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function ideHelpersStatus(
        ?ClientGateway $client = null,
        bool $withDetails = false,
        int $limit = 50,
    ): array {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $roots = $this->rootsOrFallback($runtime, $projectRoot);
            $config = KirbyMcpConfig::load($projectRoot);
            $cacheTtlSeconds = $config->cacheTtlSeconds();
            $typeHintScanBytes = $config->ideTypeHintScanBytes();

            $inputsByHelper = $this->watchedInputsByHelper($projectRoot, $roots);
            $watchedInputs = $this->flattenWatchedInputs($inputsByHelper);

            $latestByPath = $this->latestMtimeByPath($watchedInputs);
            [$latestInputMtime, $latestInputPath] = $this->latestMtimeFromCandidates($latestByPath, $watchedInputs);

            $helpers = $this->helperFilesStatus(
                projectRoot: $projectRoot,
                watchedInputsByHelper: $inputsByHelper,
                latestByPath: $latestByPath,
            );

            $indexer = new RootsCodeIndexer();
            $templatesIndex = $indexer->templates($projectRoot, $roots);
            $snippetsIndex = $indexer->snippets($projectRoot, $roots);
            $controllersIndex = $indexer->controllers($projectRoot, $roots);
            $modelsIndex = $indexer->models($projectRoot, $roots);

            $templatesCoverage = $this->kirbyVarHintsCoverage(
                entries: $templatesIndex['templates'] ?? [],
                withDetails: $withDetails,
                limit: $limit,
                cacheTtlSeconds: $cacheTtlSeconds,
            );
            $snippetsCoverage = $this->kirbyVarHintsCoverage(
                entries: $snippetsIndex['snippets'] ?? [],
                withDetails: $withDetails,
                limit: $limit,
                cacheTtlSeconds: $cacheTtlSeconds,
            );

            $controllersCoverage = $this->kirbyControllerTypeHintsCoverage(
                entries: $controllersIndex['controllers'] ?? [],
                withDetails: $withDetails,
                limit: $limit,
                cacheTtlSeconds: $cacheTtlSeconds,
                scanBytes: $typeHintScanBytes,
            );

            $pageModelsCoverage = $this->kirbyPageModelTypeHintsCoverage(
                entries: $modelsIndex['models'] ?? [],
                withDetails: $withDetails,
                limit: $limit,
                cacheTtlSeconds: $cacheTtlSeconds,
                scanBytes: $typeHintScanBytes,
            );

            $recommendations = [];
            if (($templatesCoverage['missingKirbyVarHints'] ?? 0) > 0 || ($snippetsCoverage['missingKirbyVarHints'] ?? 0) > 0) {
                $recommendations[] = 'Add Kirby template/snippet PHPDoc @var hints for $kirby/$site/$page (human IDE baseline).';
            }

            if (($controllersCoverage['missingKirbyTypeHints'] ?? 0) > 0) {
                $recommendations[] = 'Type-hint Kirby objects in controller closures (Site/Page/Pages/App) for IDE support.';
            }

            if (($pageModelsCoverage['missingKirbyTypeHints'] ?? 0) > 0) {
                $recommendations[] = 'Ensure custom page model classes extend Kirby\\Cms\\Page (and import/type it) for IDE support.';
            }

            $missingHelpers = array_values(array_filter($helpers, static fn (array $helper): bool => str_starts_with((string) ($helper['id'] ?? ''), 'kirby-mcp:') && ($helper['exists'] ?? false) !== true));
            if ($missingHelpers !== []) {
                $recommendations[] = 'Optionally run kirby_generate_ide_helpers to create regeneratable helper files in .kirby-mcp/ (improves IDE autocomplete without changing app code).';
            }

            $staleHelpers = array_values(array_filter($helpers, static fn (array $helper): bool => str_starts_with((string) ($helper['id'] ?? ''), 'kirby-mcp:') && ($helper['stale'] ?? false) === true));
            if ($staleHelpers !== []) {
                $recommendations[] = 'Run kirby_generate_ide_helpers to regenerate IDE helper files that are marked stale (mtime-based freshness).';
            }

            $kirbyTypes = null;
            foreach ($helpers as $helper) {
                if (($helper['id'] ?? null) === 'kirby-types:types.php') {
                    $kirbyTypes = $helper;
                    break;
                }
            }

            if (is_array($kirbyTypes) && ($kirbyTypes['exists'] ?? false) === true && ($kirbyTypes['stale'] ?? false) === true) {
                $recommendations[] = 'If you use lukaskleinschmidt/kirby-types, run `kirby types:create --force` to refresh types.php.';
            }

            $notes = [
                'Template/snippet var hints baseline: https://getkirby.com/docs/quicktips/ide-support#templates-and-snippets',
                'Controller and page-model type hints baseline: https://getkirby.com/docs/quicktips/ide-support#controllers',
                'This status is filesystem-based; plugin-registered templates/snippets require runtime indexing tools for full coverage.',
            ];

            return [
                'projectRoot' => $projectRoot,
                'host' => $host,
                'watchedInputs' => $watchedInputs,
                'inputs' => [
                    'latestMtime' => $latestInputMtime,
                    'latestPath' => $latestInputPath,
                ],
                'helpers' => $helpers,
                'templates' => $templatesCoverage,
                'snippets' => $snippetsCoverage,
                'controllers' => $controllersCoverage,
                'pageModels' => $pageModelsCoverage,
                'recommendations' => $recommendations,
                'notes' => $notes,
            ];
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_ide_helpers_status',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    private function rootsOrFallback(KirbyRuntimeContext $runtime, string $projectRoot): KirbyRoots
    {
        try {
            return $runtime->roots();
        } catch (\Throwable) {
            return new KirbyRoots([
                'blueprints' => $projectRoot . '/site/blueprints',
                'templates' => $projectRoot . '/site/templates',
                'snippets' => $projectRoot . '/site/snippets',
                'controllers' => $projectRoot . '/site/controllers',
                'models' => $projectRoot . '/site/models',
                'config' => $projectRoot . '/site/config',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, array{id:string, type:string, data:array<mixed>}>
     */
    private function blueprintsFromRuntimePayload(array $payload): array
    {
        $out = [];

        $list = $payload['blueprints'] ?? null;
        if (!is_array($list)) {
            return [];
        }

        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = $entry['id'] ?? null;
            if (!is_string($id) || $id === '') {
                continue;
            }

            $type = $entry['type'] ?? null;
            if (!is_string($type) || $type === '') {
                $type = $this->blueprintTypeFromId($id);
            }

            $data = $entry['data'] ?? null;
            if (!is_array($data)) {
                continue;
            }

            $out[$id] = [
                'id' => $id,
                'type' => $type,
                'data' => $data,
            ];
        }

        ksort($out);

        return $out;
    }

    private function blueprintTypeFromId(string $id): string
    {
        if (!str_contains($id, '/')) {
            return $id;
        }

        $first = explode('/', $id, 2)[0];
        return $first !== '' ? $first : 'unknown';
    }

    private function blueprintTypeFromScanner(BlueprintType $type): string
    {
        return match ($type) {
            BlueprintType::Page => 'pages',
            BlueprintType::File => 'files',
            BlueprintType::User => 'users',
            BlueprintType::Site => 'site',
            default => 'unknown',
        };
    }

    /**
     * @param array<mixed> $data
     * @return array<int, string>
     */
    private function extractFieldKeysFromBlueprint(array $data): array
    {
        $keys = [];

        $walk = function (mixed $node, bool $insideFieldDefinition) use (&$walk, &$keys): void {
            if (!is_array($node)) {
                return;
            }

            foreach ($node as $key => $value) {
                if ($key === 'fields' && is_array($value) && $insideFieldDefinition === false) {
                    foreach ($value as $fieldKey => $fieldDefinition) {
                        if (!is_string($fieldKey) || trim($fieldKey) === '') {
                            continue;
                        }

                        $keys[$fieldKey] = true;

                        // Avoid collecting nested `fields` keys from within field definitions
                        // (e.g. structure/object fields define their own internal fields).
                        $walk($fieldDefinition, true);
                    }

                    continue;
                }

                if (is_array($value)) {
                    $walk($value, $insideFieldDefinition);
                }
            }
        };

        $walk($data, false);

        $out = array_keys($keys);
        sort($out);

        return $out;
    }

    private function normalizeFieldMethodName(string $fieldKey): ?string
    {
        $fieldKey = trim($fieldKey);
        if ($fieldKey === '') {
            return null;
        }

        $method = preg_replace('/[^A-Za-z0-9_]/', '_', $fieldKey);
        if (!is_string($method) || $method === '') {
            return null;
        }

        $method = preg_replace('/_+/', '_', $method);
        if (!is_string($method) || $method === '') {
            return null;
        }

        if (ctype_digit($method[0])) {
            $method = '_' . $method;
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $method)) {
            return null;
        }

        return $method;
    }

    private function collidesWithRealMethod(string $className, string $methodName): bool
    {
        $methodName = strtolower($methodName);

        /** @var array<string, bool>|null $methods */
        $methods = StaticCache::remember('ide:methods:' . $className, static function () use ($className): array {
            if (!class_exists($className)) {
                return [];
            }

            $reflection = new \ReflectionClass($className);
            $names = [];
            foreach ($reflection->getMethods() as $method) {
                $names[strtolower($method->getName())] = true;
            }

            return $names;
        }, 3600);

        return isset($methods[$methodName]);
    }

    /**
     * @param array<string, array<string, array<string, bool>>> $methods
     */
    private function renderKirbyFieldsStub(array $methods): string
    {
        $header = <<<'PHP'
<?php

/**
 * This file was automatically generated by bnomei/kirby-mcp.
 *
 * It is intended for IDE parsing/autocomplete only.
 * Do not include/require it at runtime.
 */

PHP;

        $parts = [$header];

        $targets = [
            'pages' => ['namespace' => 'Kirby\\Cms', 'class' => 'Page'],
            'files' => ['namespace' => 'Kirby\\Cms', 'class' => 'File'],
            'users' => ['namespace' => 'Kirby\\Cms', 'class' => 'User'],
            'site' => ['namespace' => 'Kirby\\Cms', 'class' => 'Site'],
        ];

        foreach ($targets as $type => $target) {
            $methodNames = array_keys($methods[$type] ?? []);
            sort($methodNames);

            $lines = [];
            foreach ($methodNames as $method) {
                $lines[] = ' * @method \\Kirby\\Content\\Field ' . $method . '()';
            }

            $doc = "/**\n";
            $doc .= " * Blueprint field accessors (generated).\n";
            if ($lines !== []) {
                $doc .= implode("\n", $lines) . "\n";
            }
            $doc .= " */\n";

            $parts[] = 'namespace ' . $target['namespace'] . " {\n"
                . $doc
                . '    class ' . $target['class'] . " {}\n"
                . "}\n";
        }

        return implode("\n", $parts);
    }

    /**
     * @param array<int, mixed> $snippets
     */
    private function renderPhpStormMeta(array $snippets): string
    {
        $snippets = array_values(array_filter($snippets, static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
        $snippets = array_values(array_unique(array_map(static fn (string $value): string => trim($value), $snippets)));
        sort($snippets);

        $header = <<<'PHP'
<?php

/**
 * This file was automatically generated by bnomei/kirby-mcp.
 *
 * It is intended for IDE parsing/autocomplete only.
 * Do not include/require it at runtime.
 */

namespace PHPSTORM_META {

PHP;

        $lines = [];

        if ($snippets !== []) {
            $lines[] = "    registerArgumentsSet('kirby_mcp_snippets',";
            foreach ($snippets as $snippet) {
                $lines[] = '        ' . var_export($snippet, true) . ',';
            }
            $lines[] = '    );';
            $lines[] = "    expectedArguments(\\snippet(), 0, argumentsSet('kirby_mcp_snippets'));";
        } else {
            $lines[] = '    // No snippets detected.';
        }

        $footer = "\n}\n";

        return $header . implode("\n", $lines) . $footer;
    }

    /**
     * @param array<string, array<string, array<string, bool>>> $methods
     * @param array<string, array<string, array<string, bool>>> $collisions
     */
    private function renderBlueprintIndexJson(
        string $projectRoot,
        ?string $host,
        string $source,
        array $methods,
        array $collisions,
    ): string {
        $payload = [
            'generatedAt' => gmdate('c'),
            'projectRoot' => $projectRoot,
            'host' => $host,
            'source' => $source,
            'methods' => array_map(static function (array $byMethod): array {
                $out = [];
                foreach ($byMethod as $method => $blueprints) {
                    $out[$method] = array_values(array_keys($blueprints));
                }
                ksort($out);
                return $out;
            }, $methods),
            'collisions' => array_map(static function (array $byMethod): array {
                $out = [];
                foreach ($byMethod as $method => $blueprints) {
                    $out[$method] = array_values(array_keys($blueprints));
                }
                ksort($out);
                return $out;
            }, $collisions),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json . "\n" : "{}\n";
    }

    private function writePlannedFile(
        string $id,
        string $path,
        string $contents,
        bool $dryRun,
        bool $force,
    ): array {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $exists = is_file($path);
        if ($exists === true && $force === false) {
            return [
                'id' => $id,
                'path' => $path,
                'action' => 'skip',
                'reason' => 'File exists (force=false).',
                'bytes' => null,
                'ok' => true,
                'error' => null,
            ];
        }

        $action = $exists ? 'overwrite' : 'create';

        if ($dryRun === true) {
            return [
                'id' => $id,
                'path' => $path,
                'action' => $action,
                'reason' => 'dryRun=true',
                'bytes' => strlen($contents),
                'ok' => true,
                'error' => null,
            ];
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return [
                'id' => $id,
                'path' => $path,
                'action' => $action,
                'reason' => null,
                'bytes' => null,
                'ok' => false,
                'error' => 'Failed to create directory: ' . $dir,
            ];
        }

        $written = file_put_contents($path, $contents);
        if ($written === false) {
            return [
                'id' => $id,
                'path' => $path,
                'action' => $action,
                'reason' => null,
                'bytes' => null,
                'ok' => false,
                'error' => 'Failed to write file.',
            ];
        }

        return [
            'id' => $id,
            'path' => $path,
            'action' => $action,
            'reason' => null,
            'bytes' => $written,
            'ok' => true,
            'error' => null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function watchedInputs(string $projectRoot, KirbyRoots $roots): array
    {
        $paths = [];

        $composerJson = $projectRoot . '/composer.json';
        if (is_file($composerJson)) {
            $paths[] = $composerJson;
        }

        $composerLock = $projectRoot . '/composer.lock';
        if (is_file($composerLock)) {
            $paths[] = $composerLock;
        }

        $blueprintsRoot = $roots->get('blueprints') ?? ($projectRoot . '/site/blueprints');
        if (is_string($blueprintsRoot) && $blueprintsRoot !== '') {
            $paths[] = $blueprintsRoot;
        }

        $snippetsRoot = $roots->get('snippets') ?? ($projectRoot . '/site/snippets');
        if (is_string($snippetsRoot) && $snippetsRoot !== '') {
            $paths[] = $snippetsRoot;
        }

        $configRoot = $roots->get('config') ?? ($projectRoot . '/site/config');
        if (is_string($configRoot) && $configRoot !== '') {
            $paths[] = $configRoot;
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function watchedInputsByHelper(string $projectRoot, KirbyRoots $roots): array
    {
        $composerInputs = [];

        $composerJson = $projectRoot . '/composer.json';
        if (is_file($composerJson)) {
            $composerInputs[] = $composerJson;
        }

        $composerLock = $projectRoot . '/composer.lock';
        if (is_file($composerLock)) {
            $composerInputs[] = $composerLock;
        }

        $blueprintsRoot = $roots->get('blueprints') ?? ($projectRoot . '/site/blueprints');
        $blueprintsRoot = is_string($blueprintsRoot) && $blueprintsRoot !== '' ? $blueprintsRoot : null;

        $snippetsRoot = $roots->get('snippets') ?? ($projectRoot . '/site/snippets');
        $snippetsRoot = is_string($snippetsRoot) && $snippetsRoot !== '' ? $snippetsRoot : null;

        $configRoot = $roots->get('config') ?? ($projectRoot . '/site/config');
        $configRoot = is_string($configRoot) && $configRoot !== '' ? $configRoot : null;

        $blueprintsInputs = array_values(array_filter(array_merge(
            $composerInputs,
            [
                $blueprintsRoot,
                $configRoot,
            ],
        ), static fn (mixed $value): bool => is_string($value) && $value !== ''));

        $snippetsInputs = array_values(array_filter(array_merge(
            $composerInputs,
            [
                $snippetsRoot,
                $configRoot,
            ],
        ), static fn (mixed $value): bool => is_string($value) && $value !== ''));

        return [
            'kirby-mcp:_ide_helper_kirby_fields' => $blueprintsInputs,
            'kirby-mcp:blueprints-index' => $blueprintsInputs,
            'kirby-mcp:phpstorm-meta' => $snippetsInputs,
            'kirby-types:types.php' => $blueprintsInputs,
        ];
    }

    /**
     * @param array<string, array<int, string>> $watchedInputsByHelper
     * @return array<int, string>
     */
    private function flattenWatchedInputs(array $watchedInputsByHelper): array
    {
        $paths = [];
        foreach ($watchedInputsByHelper as $watchedInputs) {
            if (!is_array($watchedInputs)) {
                continue;
            }

            foreach ($watchedInputs as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }

    /**
     * @param array<int, string> $paths
     * @return array<string, array{mtime:int, path:string}|null>
     */
    private function latestMtimeByPath(array $paths): array
    {
        $out = [];
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $out[$path] = $this->latestMtimeForPath($path);
        }

        return $out;
    }

    /**
     * @param array<string, array{mtime:int, path:string}|null> $candidatesByPath
     * @param array<int, string> $paths
     * @return array{0:int|null, 1:string|null}
     */
    private function latestMtimeFromCandidates(array $candidatesByPath, array $paths): array
    {
        $latestMtime = null;
        $latestPath = null;

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $candidate = $candidatesByPath[$path] ?? null;
            if (!is_array($candidate)) {
                continue;
            }

            $mtime = $candidate['mtime'] ?? null;
            $changedPath = $candidate['path'] ?? null;
            if (!is_int($mtime) || !is_string($changedPath) || $changedPath === '') {
                continue;
            }

            if ($latestMtime === null || $mtime > $latestMtime) {
                $latestMtime = $mtime;
                $latestPath = $changedPath;
            }
        }

        return [$latestMtime, $latestPath];
    }

    /**
     * @param array<int, string> $paths
     * @return array{0:int|null, 1:string|null}
     */
    private function latestMtimeAcross(array $paths): array
    {
        $latestMtime = null;
        $latestPath = null;

        foreach ($paths as $path) {
            $candidate = $this->latestMtimeForPath($path);
            if ($candidate === null) {
                continue;
            }

            if ($latestMtime === null || $candidate['mtime'] > $latestMtime) {
                $latestMtime = $candidate['mtime'];
                $latestPath = $candidate['path'];
            }
        }

        return [$latestMtime, $latestPath];
    }

    /**
     * @return array{mtime:int, path:string}|null
     */
    private function latestMtimeForPath(string $path): ?array
    {
        if (is_file($path)) {
            $mtime = @filemtime($path);
            if (is_int($mtime)) {
                return ['mtime' => $mtime, 'path' => $path];
            }

            return null;
        }

        if (!is_dir($path)) {
            return null;
        }

        $latestMtime = null;
        $latestPath = null;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() === false) {
                continue;
            }

            $filePath = $file->getPathname();
            if ($this->isIgnoredPath($filePath)) {
                continue;
            }

            $mtime = $file->getMTime();
            if ($latestMtime === null || $mtime > $latestMtime) {
                $latestMtime = $mtime;
                $latestPath = $filePath;
            }
        }

        if ($latestMtime === null || $latestPath === null) {
            return null;
        }

        return ['mtime' => $latestMtime, 'path' => $latestPath];
    }

    private function isIgnoredPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_contains($normalized, '/.git/')
            || str_contains($normalized, '/vendor/')
            || str_contains($normalized, '/node_modules/')
            || str_contains($normalized, '/.kirby-mcp/');
    }

    /**
     * @return array<int, array{id:string, path:string, exists:bool, mtime:int|null, stale:bool|null, reason:string|null}>
     */
    private function helperFilesStatus(
        string $projectRoot,
        array $watchedInputsByHelper,
        array $latestByPath,
    ): array {
        $candidates = [
            [
                'id' => 'kirby-mcp:_ide_helper_kirby_fields',
                'path' => $projectRoot . '/.kirby-mcp/_ide_helper_kirby_fields.php',
            ],
            [
                'id' => 'kirby-mcp:phpstorm-meta',
                'path' => $projectRoot . '/.kirby-mcp/.phpstorm.meta.php/kirby.php',
            ],
            [
                'id' => 'kirby-mcp:blueprints-index',
                'path' => $projectRoot . '/.kirby-mcp/kirby-blueprints.index.json',
            ],
            [
                'id' => 'kirby-types:types.php',
                'path' => $projectRoot . '/types.php',
            ],
        ];

        $helpers = [];

        foreach ($candidates as $candidate) {
            $path = $candidate['path'];
            $exists = is_file($path);
            $mtime = $exists ? @filemtime($path) : null;

            $stale = null;
            $reason = null;

            $inputs = $watchedInputsByHelper[$candidate['id']] ?? [];
            $inputs = is_array($inputs) ? $inputs : [];

            [$latestInputMtime, $latestInputPath] = $this->latestMtimeFromCandidates($latestByPath, $inputs);

            if ($exists && is_int($mtime) && is_int($latestInputMtime) && is_string($latestInputPath)) {
                $stale = $latestInputMtime > $mtime;
                if ($stale) {
                    $reason = 'Newer input detected: ' . $latestInputPath;
                }
            }

            $helpers[] = [
                'id' => $candidate['id'],
                'path' => $path,
                'exists' => $exists,
                'mtime' => is_int($mtime) ? $mtime : null,
                'stale' => $stale,
                'reason' => $reason,
            ];
        }

        return $helpers;
    }

    /**
     * @param array<string, array{id?:string, name?:string, absolutePath?:string, relativePath?:string}> $entries
     * @return array{total:int, withKirbyVarHints:int, missingKirbyVarHints:int, missing: array<int, array{id:string, relativePath: string|null, absolutePath: string|null}>}
     */
    private function kirbyVarHintsCoverage(array $entries, bool $withDetails, int $limit, int $cacheTtlSeconds): array
    {
        $total = 0;
        $withHints = 0;
        $missing = [];

        foreach ($entries as $id => $entry) {
            $total++;

            $absolutePath = $entry['absolutePath'] ?? null;
            $hasHints = is_string($absolutePath) ? $this->hasKirbyVarHints($absolutePath, $cacheTtlSeconds) : false;

            if ($hasHints) {
                $withHints++;
                continue;
            }

            if (count($missing) < max(0, $limit)) {
                $missing[] = [
                    'id' => is_string($entry['id'] ?? null) ? $entry['id'] : (is_string($id) ? $id : ''),
                    'relativePath' => is_string($entry['relativePath'] ?? null) ? $entry['relativePath'] : null,
                    'absolutePath' => $withDetails ? (is_string($absolutePath) ? $absolutePath : null) : null,
                ];
            }
        }

        return [
            'total' => $total,
            'withKirbyVarHints' => $withHints,
            'missingKirbyVarHints' => max(0, $total - $withHints),
            'missing' => $missing,
        ];
    }

    /**
     * @param array<string, array{id?:string, name?:string, absolutePath?:string, relativePath?:string}> $entries
     * @return array{
     *   total:int,
     *   closureControllers:int,
     *   withKirbyTypeHints:int,
     *   missingKirbyTypeHints:int,
     *   missing: array<int, array{id:string, relativePath: string|null, absolutePath: string|null}>
     * }
     */
    private function kirbyControllerTypeHintsCoverage(array $entries, bool $withDetails, int $limit, int $cacheTtlSeconds, int $scanBytes): array
    {
        $total = 0;
        $closures = 0;
        $withHints = 0;
        $missingHints = 0;
        $missing = [];

        foreach ($entries as $id => $entry) {
            $total++;

            $absolutePath = $entry['absolutePath'] ?? null;
            if (!is_string($absolutePath) || $absolutePath === '') {
                continue;
            }

            $info = $this->controllerTypeHintInfo($absolutePath, $cacheTtlSeconds, $scanBytes);
            if (($info['isClosure'] ?? false) !== true) {
                continue;
            }

            $closures++;

            if (($info['typed'] ?? false) === true) {
                $withHints++;
                continue;
            }

            $missingHints++;

            if (count($missing) < max(0, $limit)) {
                $missing[] = [
                    'id' => is_string($entry['id'] ?? null) ? $entry['id'] : (is_string($id) ? $id : ''),
                    'relativePath' => is_string($entry['relativePath'] ?? null) ? $entry['relativePath'] : null,
                    'absolutePath' => $withDetails ? $absolutePath : null,
                ];
            }
        }

        return [
            'total' => $total,
            'closureControllers' => $closures,
            'withKirbyTypeHints' => $withHints,
            'missingKirbyTypeHints' => $missingHints,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{isClosure: bool, typed: bool}
     */
    private function controllerTypeHintInfo(string $absolutePath, int $cacheTtlSeconds, int $scanBytes): array
    {
        $mtime = @filemtime($absolutePath);
        $cacheKey = null;
        if (is_int($mtime) && $cacheTtlSeconds > 0) {
            $cacheKey = 'ide:controllerTypeHints:' . $absolutePath . ':' . $mtime . ':' . $scanBytes;
        }

        if (is_string($cacheKey)) {
            return (array) StaticCache::remember($cacheKey, function () use ($absolutePath, $scanBytes): array {
                return $this->controllerTypeHintInfoUncached($absolutePath, $scanBytes);
            }, $cacheTtlSeconds);
        }

        return $this->controllerTypeHintInfoUncached($absolutePath, $scanBytes);
    }

    /**
     * @return array{isClosure: bool, typed: bool}
     */
    private function controllerTypeHintInfoUncached(string $absolutePath, int $scanBytes): array
    {
        $head = $this->readFileHead($absolutePath, $scanBytes);
        if ($head === null || $head === '') {
            return ['isClosure' => false, 'typed' => false];
        }

        if (preg_match('/return\\s+function\\s*\\(/', $head) !== 1) {
            return ['isClosure' => false, 'typed' => true];
        }

        $matches = [];
        if (preg_match('/return\\s+function\\s*\\((.*?)\\)/s', $head, $matches) !== 1) {
            return ['isClosure' => true, 'typed' => false];
        }

        $params = $matches[1] ?? '';
        if (!is_string($params)) {
            $params = '';
        }

        $targets = [
            'site' => ['var' => '$site', 'type' => 'Site', 'fqn' => 'Kirby\\Cms\\Site'],
            'page' => ['var' => '$page', 'type' => 'Page', 'fqn' => 'Kirby\\Cms\\Page'],
            'pages' => ['var' => '$pages', 'type' => 'Pages', 'fqn' => 'Kirby\\Cms\\Pages'],
            'kirby' => ['var' => '$kirby', 'type' => 'App', 'fqn' => 'Kirby\\Cms\\App'],
        ];

        $present = 0;
        $typed = 0;

        foreach ($targets as $target) {
            $varName = $target['var'];
            $short = $target['type'];
            $fqn = $target['fqn'];

            if (preg_match('/\\' . preg_quote($varName, '/') . '\\b/', $params) !== 1) {
                continue;
            }

            $present++;

            $pattern = '/(?:\\\\?' . str_replace('\\', '\\\\', $fqn) . '|\\b' . preg_quote($short, '/') . '\\b)\\s+\\' . preg_quote($varName, '/') . '\\b/';
            if (preg_match($pattern, $params) === 1) {
                $typed++;
            }
        }

        if ($present === 0) {
            return ['isClosure' => true, 'typed' => true];
        }

        return ['isClosure' => true, 'typed' => $typed === $present];
    }

    /**
     * @param array<string, array{id?:string, name?:string, absolutePath?:string, relativePath?:string}> $entries
     * @return array{
     *   total:int,
     *   pageModelFiles:int,
     *   withKirbyTypeHints:int,
     *   missingKirbyTypeHints:int,
     *   missing: array<int, array{id:string, relativePath: string|null, absolutePath: string|null}>
     * }
     */
    private function kirbyPageModelTypeHintsCoverage(array $entries, bool $withDetails, int $limit, int $cacheTtlSeconds, int $scanBytes): array
    {
        $total = 0;
        $pageModels = 0;
        $withHints = 0;
        $missingHints = 0;
        $missing = [];

        foreach ($entries as $id => $entry) {
            $total++;

            $absolutePath = $entry['absolutePath'] ?? null;
            if (!is_string($absolutePath) || $absolutePath === '') {
                continue;
            }

            $info = $this->pageModelTypeHintInfo($absolutePath, $cacheTtlSeconds, $scanBytes);
            if (($info['isPageModel'] ?? false) !== true) {
                continue;
            }

            $pageModels++;

            if (($info['typed'] ?? false) === true) {
                $withHints++;
                continue;
            }

            $missingHints++;

            if (count($missing) < max(0, $limit)) {
                $missing[] = [
                    'id' => is_string($entry['id'] ?? null) ? $entry['id'] : (is_string($id) ? $id : ''),
                    'relativePath' => is_string($entry['relativePath'] ?? null) ? $entry['relativePath'] : null,
                    'absolutePath' => $withDetails ? $absolutePath : null,
                ];
            }
        }

        return [
            'total' => $total,
            'pageModelFiles' => $pageModels,
            'withKirbyTypeHints' => $withHints,
            'missingKirbyTypeHints' => $missingHints,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{isPageModel: bool, typed: bool}
     */
    private function pageModelTypeHintInfo(string $absolutePath, int $cacheTtlSeconds, int $scanBytes): array
    {
        $mtime = @filemtime($absolutePath);
        $cacheKey = null;
        if (is_int($mtime) && $cacheTtlSeconds > 0) {
            $cacheKey = 'ide:pageModelTypeHints:' . $absolutePath . ':' . $mtime . ':' . $scanBytes;
        }

        if (is_string($cacheKey)) {
            return (array) StaticCache::remember($cacheKey, function () use ($absolutePath, $scanBytes): array {
                return $this->pageModelTypeHintInfoUncached($absolutePath, $scanBytes);
            }, $cacheTtlSeconds);
        }

        return $this->pageModelTypeHintInfoUncached($absolutePath, $scanBytes);
    }

    /**
     * @return array{isPageModel: bool, typed: bool}
     */
    private function pageModelTypeHintInfoUncached(string $absolutePath, int $scanBytes): array
    {
        $head = $this->readFileHead($absolutePath, $scanBytes);
        if ($head === null || $head === '') {
            return ['isPageModel' => false, 'typed' => false];
        }

        if (preg_match('/class\\s+\\w*Page\\b/', $head) !== 1) {
            return ['isPageModel' => false, 'typed' => true];
        }

        if (preg_match('/extends\\s+\\\\?Kirby\\\\Cms\\\\Page\\b/', $head) === 1) {
            return ['isPageModel' => true, 'typed' => true];
        }

        $matches = [];
        $alias = null;
        if (preg_match('/use\\s+Kirby\\\\Cms\\\\Page\\s*(?:as\\s+(\\w+))?\\s*;/', $head, $matches) === 1) {
            $alias = is_string($matches[1] ?? null) && $matches[1] !== '' ? $matches[1] : 'Page';
        }

        if (is_string($alias) && $alias !== '') {
            $pattern = '/class\\s+\\w*Page\\b.*?extends\\s+' . preg_quote($alias, '/') . '\\b/s';
            if (preg_match($pattern, $head) === 1) {
                return ['isPageModel' => true, 'typed' => true];
            }
        }

        return ['isPageModel' => true, 'typed' => false];
    }

    private function hasKirbyVarHints(string $absolutePath, int $cacheTtlSeconds): bool
    {
        $mtime = @filemtime($absolutePath);
        $cacheKey = null;
        if (is_int($mtime) && $cacheTtlSeconds > 0) {
            $cacheKey = 'ide:kirbyVarHints:' . $absolutePath . ':' . $mtime;
        }

        if (is_string($cacheKey)) {
            return (bool) StaticCache::remember($cacheKey, function () use ($absolutePath): bool {
                $head = $this->readFileHead($absolutePath, 8192);
                if ($head === null || $head === '') {
                    return false;
                }

                $hasKirby = preg_match('/@var\\s+\\\\?Kirby\\\\Cms\\\\App\\s+\\$kirby\\b/', $head) === 1;
                $hasSite = preg_match('/@var\\s+\\\\?Kirby\\\\Cms\\\\Site\\s+\\$site\\b/', $head) === 1;
                $hasPage = preg_match('/@var\\s+\\\\?Kirby\\\\Cms\\\\Page\\s+\\$page\\b/', $head) === 1;

                return $hasKirby && $hasSite && $hasPage;
            }, $cacheTtlSeconds);
        }

        $head = $this->readFileHead($absolutePath, 8192);
        if ($head === null || $head === '') {
            return false;
        }

        $hasKirby = preg_match('/@var\\s+\\\\?Kirby\\\\Cms\\\\App\\s+\\$kirby\\b/', $head) === 1;
        $hasSite = preg_match('/@var\\s+\\\\?Kirby\\\\Cms\\\\Site\\s+\\$site\\b/', $head) === 1;
        $hasPage = preg_match('/@var\\s+\\\\?Kirby\\\\Cms\\\\Page\\s+\\$page\\b/', $head) === 1;

        return $hasKirby && $hasSite && $hasPage;
    }

    private function readFileHead(string $path, int $maxBytes): ?string
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            $data = fread($handle, max(1, $maxBytes));
            return is_string($data) ? $data : null;
        } finally {
            fclose($handle);
        }
    }
}
