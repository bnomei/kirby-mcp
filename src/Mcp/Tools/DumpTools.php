<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Dumps\DumpLogReader;
use Bnomei\KirbyMcp\Dumps\DumpLogWriter;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\DumpState;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

final class DumpTools
{
    use StructuredToolResult;

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Tail `mcp_dump()` output captured to `.kirby-mcp/dumps.jsonl`.
     *
     * @return array<string, mixed>
     */
    #[McpToolIndex(
        whenToUse: 'Use after rendering a page to retrieve recent `mcp_dump()` output. Pass `traceId` returned by kirby_render_page (best), or filter by `path` like `/about`.',
        keywords: [
            'dump' => 100,
            'mcp_dump' => 100,
            'logs' => 60,
            'tail' => 60,
            'debug' => 50,
            'traceid' => 50,
            'trace' => 30,
            'caller' => 30,
            'ray' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_dump_log_tail',
        title: 'Dump Log Tail',
        description: 'Tail `.kirby-mcp/dumps.jsonl` written by `mcp_dump()` and return structured JSON. Requires a filter — `traceId` (from kirby_render_page or this session\'s last render) or `path` — because the log is shared across sessions; an unfiltered tail returns no events. `limit=0` returns all matching events.',
        annotations: new ToolAnnotations(
            title: 'Dump Log Tail',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function dumpLogTail(
        ?string $traceId = null,
        ?string $path = null,
        int $limit = 50,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $projectRoot = $this->context->projectRoot();

            $traceId = is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : DumpState::lastTraceId($context?->getSession());
            $normalizedPath = is_string($path) && trim($path) !== '' ? trim($path) : null;

            // The dump log (`.kirby-mcp/dumps.jsonl`) is shared per project and
            // is written by every render/request regardless of which MCP session
            // triggered it. Reading it with no filter would return another
            // client's debug output (possibly PII, tokens, config snippets) on
            // shared HTTP deployments or multi-agent stdio. Require a filter:
            // a `traceId` (from kirby_render_page, or this session's last render)
            // or an explicit `path`. A session that has not rendered yet resolves
            // to no traceId and must scope its query rather than draining the log.
            if ($traceId === null && $normalizedPath === null) {
                $payload = [
                    'ok' => true,
                    'projectRoot' => $projectRoot,
                    'file' => DumpLogWriter::filePath($projectRoot),
                    'traceId' => null,
                    'path' => null,
                    'limit' => max(0, $limit),
                    'count' => 0,
                    'events' => [],
                    'note' => 'Provide a traceId (returned by kirby_render_page) or a path filter. The dump log is shared across sessions, so unfiltered tails are not returned.',
                ];

                return $this->maybeStructuredResult($context, $payload);
            }

            $events = DumpLogReader::tail(
                projectRoot: $projectRoot,
                traceId: $traceId,
                path: $normalizedPath,
                limit: $limit,
            );

            $payload = [
                'ok' => true,
                'projectRoot' => $projectRoot,
                'file' => DumpLogWriter::filePath($projectRoot),
                'traceId' => $traceId,
                'path' => $normalizedPath,
                'limit' => max(0, $limit),
                'count' => count($events),
                'events' => $events,
            ];

            return $this->maybeStructuredResult($context, $payload);
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_dump_log_tail',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }
}
