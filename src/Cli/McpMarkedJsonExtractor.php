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
