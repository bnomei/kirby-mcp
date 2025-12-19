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
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class RoutesTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use to list registered Kirby routes (runtime truth) and where their action callback is defined (best-effort via reflection). Useful for debugging routing and custom endpoints.',
        keywords: [
            'route' => 100,
            'routes' => 100,
            'routing' => 60,
            'router' => 60,
            'pattern' => 50,
            'methods' => 40,
            'endpoint' => 30,
            'json' => 20,
            'debug' => 20,
            'source' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_routes_index',
        description: 'List registered Kirby routes with pattern/method/name and best-effort source location for the action callback. Requires `kirby_runtime_install` first.',
        annotations: new ToolAnnotations(
            title: 'Routes Index',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function routesIndex(
        ?ClientGateway $client = null,
        bool $patternsOnly = false,
        ?string $method = null,
        ?string $patternContains = null,
        int $limit = 0,
        int $cursor = 0,
        bool $debug = false,
    ): array {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();

            $args = [RuntimeCommands::ROUTES];

            if ($patternsOnly === true) {
                $args[] = '--patterns-only';
            }

            if (is_string($method) && trim($method) !== '') {
                $args[] = '--method=' . trim($method);
            }

            if (is_string($patternContains) && trim($patternContains) !== '') {
                $args[] = '--pattern-contains=' . trim($patternContains);
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
                expectedCommandRelativePath: RuntimeCommands::ROUTES_FILE,
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
                    'cliMeta' => $result->cliMeta(),
                    'message' => $debug === true ? null : RuntimeCommandResult::DEBUG_RETRY_MESSAGE,
                    'cli' => $debug === true ? $result->cli() : null,
                ]);
            }

            /** @var array<string, mixed> $payload */
            $payload = $result->payload;

            $response = array_merge($payload, [
                'mode' => 'runtime',
                'projectRoot' => $projectRoot,
                'host' => $host,
                'cliMeta' => $result->cliMeta(),
            ]);

            if ($debug === true) {
                $response['cli'] = $result->cli();
            }

            return $response;
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_routes_index',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }
}
