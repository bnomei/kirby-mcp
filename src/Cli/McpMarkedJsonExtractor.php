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
        $end = strpos($stdout, JsonMarkers::END, $start);
        if ($end === false) {
            return null;
        }

        $json = trim(substr($stdout, $start, $end - $start));
        if ($json === '') {
            return null;
        }

        return Json::decodeString($json);
    }
}
