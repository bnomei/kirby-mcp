<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

use Bnomei\KirbyMcp\Runtime\McpJsonMarkers;
use Bnomei\KirbyMcp\Support\Json;

final class McpMarkedJsonExtractor
{
    /**
     * Extract JSON wrapped in McpJsonMarkers from stdout.
     *
     * @return array<mixed>|null
     */
    public static function extract(string $stdout): ?array
    {
        $start = strpos($stdout, McpJsonMarkers::START);
        if ($start === false) {
            return null;
        }

        $start += strlen(McpJsonMarkers::START);
        $end = strpos($stdout, McpJsonMarkers::END, $start);
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
