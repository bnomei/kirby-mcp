<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\McpDumpContext;

it('prefers env trace id and falls back to generated values', function (): void {
    $original = getenv(McpDumpContext::ENV_TRACE_ID);
    putenv(McpDumpContext::ENV_TRACE_ID . '=trace-env');

    try {
        McpDumpContext::reset();

        expect(McpDumpContext::traceId())->toBe('trace-env');

        putenv(McpDumpContext::ENV_TRACE_ID);
        McpDumpContext::reset();

        $first = McpDumpContext::traceId();
        $second = McpDumpContext::traceId();

        expect($first)->toBeString()->not()->toBe('');
        expect($second)->toBe($first);
    } finally {
        if ($original === false) {
            putenv(McpDumpContext::ENV_TRACE_ID);
        } else {
            putenv(McpDumpContext::ENV_TRACE_ID . '=' . $original);
        }
    }
});

it('stores and clears the trace id manually', function (): void {
    McpDumpContext::reset();

    McpDumpContext::setTraceId('trace-manual');
    expect(McpDumpContext::traceId())->toBe('trace-manual');

    McpDumpContext::setTraceId(null);
    $generated = McpDumpContext::traceId();

    expect($generated)->toBeString()->not()->toBe('');
    expect($generated)->not()->toBe('trace-manual');
});
