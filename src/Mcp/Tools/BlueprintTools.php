<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Blueprint\BlueprintScanner;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Completion\BlueprintIdCompletionProvider;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\FieldSchemaHelper;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandResult;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Bnomei\KirbyMcp\Support\IndexList;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

final class BlueprintTools
{
    use StructuredToolResult;

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Index blueprints (prefers runtime when installed).
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to understand the project’s “schema/models” by indexing Kirby blueprints, including plugin-registered ones. Prefers Kirby runtime truth when available.',
        keywords: [
            'blueprint' => 100,
            'blueprints' => 100,
            'yaml' => 80,
            'yml' => 80,
            'panel' => 60,
            'fields' => 50,
            'sections' => 50,
            'blocks' => 40,
            'tabs' => 40,
            'schema' => 50,
            'extends' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_blueprints_index',
        description: 'Index Kirby blueprints keyed by id (e.g. pages/home). Default is a small summary (no full data, no raw CLI output) and includes derived displayName (title/name/label) plus source info (file vs extension override). Prefers runtime `kirby mcp:blueprints` (includes plugin-registered blueprints); falls back to filesystem scan when runtime commands are not installed. Supports idsOnly, fields selection, filters, and pagination to avoid truncation.',
        annotations: new ToolAnnotations(
            title: 'Blueprints Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function blueprintsIndex(
        bool $withData = false,
        bool $idsOnly = false,
        ?array $fields = null,
        ?string $type = null,
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
            $blueprintsRoot = $runtime->root('blueprints', $projectRoot . '/site/blueprints') ?? ($projectRoot . '/site/blueprints');

            $args = [RuntimeCommands::BLUEPRINTS];
            if ($idsOnly === true) {
                $args[] = '--ids-only';
            } elseif ($withData === true) {
                $args[] = '--with-data';
            } else {
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

            $result = (new RuntimeCommandRunner($runtime))->runMarkedJson(
                expectedCommandRelativePath: RuntimeCommands::BLUEPRINTS_FILE,
                args: $args,
                timeoutSeconds: 60,
            );

            if ($result->installed === true) {
                if (!is_array($result->payload)) {
                    return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                        'mode' => 'runtime',
                        'projectRoot' => $projectRoot,
                        'host' => $host,
                        'blueprintsRoot' => $blueprintsRoot,
                        'cliMeta' => $result->cliMeta(),
                        'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                        'cli' => $debug === true ? $result->cli() : null,
                    ]));
                }

                /** @var array<string, mixed> $payload */
                $payload = $result->payload;

                $list = $payload['blueprints'] ?? null;
                $byId = [];
                $ids = [];
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

                        /** @var array<string, mixed> $entry */
                        $fieldSchemas = $entry['fieldSchemas'] ?? null;
                        if (!is_array($fieldSchemas)) {
                            $fieldSchemas = is_array($entry['data'] ?? null)
                                ? FieldSchemaHelper::fromBlueprintData($entry['data'])
                                : [];
                        }
                        $entry['fieldSchemas'] = $fieldSchemas;
                        $byId[$id] = IndexList::selectFields($entry, $fields, $id);
                    }
                }

                $ids = array_values(array_unique($ids));
                sort($ids);

                if ($idsOnly === false) {
                    ksort($byId);
                }

                $response = [
                    'ok' => $payload['ok'] ?? true,
                    'mode' => 'runtime',
                    'projectRoot' => $projectRoot,
                    'host' => $host,
                    'blueprintsRoot' => $payload['blueprintsRoot'] ?? $blueprintsRoot,
                    'counts' => $payload['counts'] ?? null,
                    'pagination' => $payload['pagination'] ?? null,
                    'filters' => $payload['filters'] ?? null,
                    'errors' => $payload['errors'] ?? [],
                    'cliMeta' => $result->cliMeta(),
                ];

                if ($idsOnly === true) {
                    $response['blueprintIds'] = $ids;
                } else {
                    $response['blueprints'] = $byId;
                }

                if ($debug === true) {
                    $response['cli'] = $result->cli();
                }

                return $this->maybeStructuredResult($context, $response);
            }

            $scan = (new BlueprintScanner())->scan($projectRoot, $blueprintsRoot);
            $blueprints = [];
            $withDataCount = 0;
            $ids = array_keys($scan->blueprints);
            sort($ids);

            if ($type !== null && trim($type) !== '') {
                $allowed = array_filter(array_map('trim', explode(',', $type)));
                if ($allowed !== []) {
                    $allowedSet = array_fill_keys($allowed, true);
                    $ids = array_values(array_filter($ids, static function (string $id) use ($allowedSet, $scan): bool {
                        $type = $scan->blueprints[$id]?->type->value ?? 'unknown';
                        return isset($allowedSet[$type]);
                    }));
                }
            }

            $activeSourceFilter = is_string($activeSource) ? strtolower(trim($activeSource)) : null;
            if ($activeSourceFilter === 'extension') {
                $ids = [];
            }

            if ($overriddenOnly === true) {
                $ids = [];
            }

            $pagination = IndexList::paginateIds($ids, $cursor, $limit);
            $pagedIds = $pagination['ids'];
            $paginationMeta = $pagination['pagination'];
            $returned = $paginationMeta['returned'];
            $total = $paginationMeta['total'];

            foreach ($pagedIds as $id) {
                $file = $scan->blueprints[$id] ?? null;
                if ($file === null) {
                    continue;
                }

                $dataError = null;
                if (!is_array($file->data)) {
                    $dataError = [
                        'class' => 'RuntimeException',
                        'message' => 'Blueprint YAML could not be parsed.',
                        'code' => 0,
                    ];
                } elseif ($withData === true) {
                    $withDataCount++;
                }

                if ($idsOnly === true) {
                    continue;
                }

                $entry = [
                    'id' => $id,
                    'type' => $file->type->value,
                    'activeSource' => 'file',
                    'sources' => ['file'],
                    'overriddenByFile' => false,
                    'file' => [
                        'active' => [
                            'absolutePath' => $file->absolutePath,
                        ],
                        'blueprintsRoot' => [
                            'absolutePath' => $file->absolutePath,
                            'relativeToBlueprintsRoot' => $file->relativePath,
                        ],
                        'extension' => null,
                    ],
                    'displayName' => $file->displayName(),
                    'displayNameSource' => $file->displayNameSource(),
                    'data' => $withData === true ? $file->data : null,
                    'dataError' => $dataError,
                    'fieldSchemas' => is_array($file->data)
                        ? FieldSchemaHelper::fromBlueprintData($file->data)
                        : [],
                ];

                $blueprints[$id] = IndexList::selectFields($entry, $fields, $id);
            }

            if ($idsOnly === false) {
                ksort($blueprints);
            }

            $response = [
                'ok' => true,
                'mode' => 'filesystem',
                'needsRuntimeInstall' => true,
                'message' => 'Runtime CLI commands are not installed; only filesystem blueprints are indexed. Run kirby_runtime_install to include plugin-registered blueprints.',
                'projectRoot' => $projectRoot,
                'host' => $host,
                'blueprintsRoot' => $blueprintsRoot,
                'filters' => [
                    'type' => $type,
                    'activeSource' => $activeSource,
                    'overriddenOnly' => $overriddenOnly,
                ],
                'pagination' => $paginationMeta,
                'counts' => [
                    'extensions' => 0,
                    'files' => $total,
                    'total' => $total,
                    'filtered' => $total,
                    'returned' => $returned,
                    'overriddenByFile' => 0,
                    'withData' => $withDataCount,
                    'loadErrors' => count($scan->errors),
                ],
                'errors' => $scan->errors,
            ];

            if ($idsOnly === true) {
                $response['blueprintIds'] = $pagedIds;
            } else {
                $response['blueprints'] = $blueprints;
            }

            return $this->maybeStructuredResult($context, $response);
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_blueprints_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * Read a single blueprint by id (prefers runtime when installed).
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use when you need the full details of a single blueprint (fields, sections, tabs, extends) without indexing every blueprint. Prefer this over kirby_blueprints_index withData=true.',
        keywords: [
            'blueprint' => 100,
            'read' => 80,
            'details' => 70,
            'fields' => 50,
            'sections' => 40,
            'tabs' => 40,
            'extends' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_blueprint_read',
        description: 'Read a single Kirby blueprint by id (e.g. pages/home). Prefers runtime `kirby mcp:blueprint` (includes plugin-registered blueprints) and returns structured JSON. Use withData=false to omit the large data payload and avoid truncation. Set debug=true to include CLI stdout/stderr. Resource template: `kirby://blueprint/{encodedId}`.',
        annotations: new ToolAnnotations(
            title: 'Blueprint Read',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function blueprintRead(
        #[CompletionProvider(provider: BlueprintIdCompletionProvider::class)]
        string $id = '',
        bool $withData = true,
        bool $debug = false,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $id = trim($id);
            if ($id === '') {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'error' => [
                        'class' => 'InvalidArgumentException',
                        'message' => 'Blueprint id must not be empty.',
                        'code' => 0,
                    ],
                ]);
            }

            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();
            $blueprintsRoot = $runtime->root('blueprints', $projectRoot . '/site/blueprints') ?? ($projectRoot . '/site/blueprints');

            $args = [RuntimeCommands::BLUEPRINT, $id];
            if ($debug === true) {
                $args[] = '--debug';
            }

            $result = (new RuntimeCommandRunner($runtime))->runMarkedJson(
                expectedCommandRelativePath: RuntimeCommands::BLUEPRINT_FILE,
                args: $args,
                timeoutSeconds: 60,
            );

            if ($result->installed === true) {
                if (!is_array($result->payload)) {
                    return $this->maybeStructuredResult($context, $result->parseErrorResponse([
                        'mode' => 'runtime',
                        'projectRoot' => $projectRoot,
                        'host' => $host,
                        'blueprintsRoot' => $blueprintsRoot,
                        'id' => $id,
                        'cliMeta' => $result->cliMeta(),
                        'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                        'cli' => $debug === true ? $result->cli() : null,
                    ]));
                }

                /** @var array<string, mixed> $response */
                $response = array_merge($result->payload, [
                    'mode' => 'runtime',
                    'projectRoot' => $projectRoot,
                    'host' => $host,
                    'blueprintsRoot' => $blueprintsRoot,
                    'cliMeta' => $result->cliMeta(),
                ]);

                if (!is_array($response['fieldSchemas'] ?? null)) {
                    if (is_array($response['data'] ?? null)) {
                        $response['fieldSchemas'] = FieldSchemaHelper::fromBlueprintData($response['data']);
                    } else {
                        $response['fieldSchemas'] = [];
                    }
                }

                if ($withData === false) {
                    unset($response['data']);
                }

                if ($debug === true) {
                    $response['cli'] = $result->cli();
                }

                return $this->maybeStructuredResult($context, $response);
            }

            $scan = (new BlueprintScanner())->scan($projectRoot, $blueprintsRoot);
            $file = $scan->blueprints[$id] ?? null;

            if ($file === null) {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'mode' => 'filesystem',
                    'needsRuntimeInstall' => true,
                    'projectRoot' => $projectRoot,
                    'host' => $host,
                    'blueprintsRoot' => $blueprintsRoot,
                    'id' => $id,
                    'error' => [
                        'class' => 'RuntimeException',
                        'message' => 'Blueprint not found in the project blueprints root. Install runtime CLI commands to resolve plugin-provided blueprints.',
                        'code' => 0,
                    ],
                ]);
            }

            $dataError = null;
            if (!is_array($file->data)) {
                $dataError = [
                    'class' => 'RuntimeException',
                    'message' => 'Blueprint YAML could not be parsed.',
                    'code' => 0,
                ];
            }

            $response = [
                'ok' => true,
                'mode' => 'filesystem',
                'needsRuntimeInstall' => true,
                'projectRoot' => $projectRoot,
                'host' => $host,
                'blueprintsRoot' => $blueprintsRoot,
                'id' => $id,
                'type' => $file->type->value,
                'displayName' => $file->displayName(),
                'displayNameSource' => $file->displayNameSource(),
                'file' => [
                    'activeSource' => 'file',
                    'overriddenByFile' => false,
                    'file' => [
                        'absolutePath' => $file->absolutePath,
                        'relativeToBlueprintsRoot' => $file->relativePath,
                    ],
                    'extension' => null,
                ],
                'dataError' => $dataError,
                'fieldSchemas' => is_array($file->data)
                    ? FieldSchemaHelper::fromBlueprintData($file->data)
                    : [],
            ];

            if ($withData === true) {
                $response['data'] = $file->data;
            }

            return $this->maybeStructuredResult($context, $response);
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_blueprint_read',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

}
