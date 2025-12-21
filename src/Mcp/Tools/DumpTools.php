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
        description: 'Tail `.kirby-mcp/dumps.jsonl` written by `mcp_dump()` and return structured JSON. Filters: `traceId`, `path`. `limit=0` returns all.',
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

            $events = DumpLogReader::tail(
                projectRoot: $projectRoot,
                traceId: $traceId,
                path: $path,
                limit: $limit,
            );

            $payload = [
                'ok' => true,
                'projectRoot' => $projectRoot,
                'file' => DumpLogWriter::filePath($projectRoot),
                'traceId' => $traceId,
                'path' => is_string($path) && trim($path) !== '' ? trim($path) : null,
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
