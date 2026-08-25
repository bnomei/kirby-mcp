<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Server\RequestContext;
use Throwable;

final class McpLog
{
    public static function error(?RequestContext $context, mixed $data, ?string $logger = 'kirby-mcp'): void
    {
        try {
            $diagnostic = ['level' => 'error', 'logger' => $logger, 'data' => self::transportSafe($data)];
            $traceparent = self::traceparent($context);
            if ($traceparent !== null) {
                $diagnostic['traceparent'] = $traceparent;
            }

            $line = json_encode(
                $diagnostic,
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR,
            );

            if (!is_string($line)) {
                throw new \RuntimeException('Could not encode diagnostic.');
            }
        } catch (Throwable) {
            $line = json_encode(
                ['level' => 'error', 'logger' => 'kirby-mcp', 'data' => ['type' => get_debug_type($data)]],
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR,
            );
            $line = is_string($line) ? $line : '{"level":"error","logger":"kirby-mcp","data":{"type":"unknown"}}';
        }

        try {
            if (PHP_SAPI === 'cli') {
                @fwrite(STDERR, $line . PHP_EOL);

                return;
            }

            @error_log($line);
        } catch (Throwable) {
            // Diagnostics must never mask the original failure.
        }
    }

    private static function traceparent(?RequestContext $context): ?string
    {
        if ($context === null) {
            return null;
        }

        $traceparent = $context->getTraceContext()['traceparent'] ?? null;
        if (!is_string($traceparent) || preg_match('/^00-([0-9a-f]{32})-([0-9a-f]{16})-[0-9a-f]{2}$/D', $traceparent, $matches) !== 1) {
            return null;
        }

        if ($matches[1] === str_repeat('0', 32) || $matches[2] === str_repeat('0', 16)) {
            return null;
        }

        return $traceparent;
    }

    private static function transportSafe(mixed $data, int $depth = 0): mixed
    {
        if (is_object($data) || is_resource($data)) {
            return ['type' => get_debug_type($data)];
        }

        if (!is_array($data)) {
            return $data;
        }

        if ($depth >= 8) {
            return ['type' => 'array'];
        }

        foreach ($data as $key => $value) {
            $data[$key] = self::transportSafe($value, $depth + 1);
        }

        return $data;
    }
}
