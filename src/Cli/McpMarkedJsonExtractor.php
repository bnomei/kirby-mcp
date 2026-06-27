<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

use Bnomei\KirbyMcp\Mcp\Support\JsonMarkers;
use Bnomei\KirbyMcp\Support\Json;

final class McpMarkedJsonExtractor
{
    /**
     * Extract JSON wrapped in JsonMarkers from stdout.
     *
     * @return array<mixed>|null
     */
    public static function extract(string $stdout): ?array
    {
        $start = strpos($stdout, JsonMarkers::START);
        if ($start === false) {
            return null;
        }

        $start += strlen(JsonMarkers::START);

        // The emitter prints START, the JSON payload, and END each on their own
        // line. A content value can legitimately contain the END marker as a
        // substring, but never as a standalone line: the JSON payload is
        // single-line-per-string (newlines are escaped), so an embedded marker
        // always shares its physical line with JSON tokens. Anchor on the last
        // line that is exactly the END marker so content can never truncate the
        // framed payload.
        $end = self::lastStandaloneMarkerOffset($stdout, JsonMarkers::END, $start);
        if ($end === null) {
            return null;
        }

        $json = trim(substr($stdout, $start, $end - $start));
        if ($json === '') {
            return null;
        }

        return Json::decodeString($json);
    }

    /**
     * Offset of the last occurrence of $marker that occupies its own line at or
     * after $from. Falls back to the last raw occurrence if no standalone line
     * is found (defensive against framing changes).
     */
    private static function lastStandaloneMarkerOffset(string $stdout, string $marker, int $from): ?int
    {
        $pattern = '/^' . preg_quote($marker, '/') . '\r?$/m';
        if (preg_match_all($pattern, $stdout, $matches, PREG_OFFSET_CAPTURE) >= 1) {
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $offset = $matches[0][$i][1];
                if ($offset >= $from) {
                    return $offset;
                }
            }
        }

        $fallback = strrpos($stdout, $marker, $from);

        return $fallback === false ? null : $fallback;
    }
}
