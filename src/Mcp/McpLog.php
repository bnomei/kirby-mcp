<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Schema\Enum\LoggingLevel;
use Mcp\Server\ClientGateway;

final class McpLog
{
    public static function log(?ClientGateway $client, LoggingLevel $level, mixed $data, ?string $logger = 'kirby-mcp'): void
    {
        if ($client === null) {
            return;
        }

        if (!LoggingState::allows($level)) {
            return;
        }

        $client->log($level, $data, $logger);
    }

    public static function error(?ClientGateway $client, mixed $data, ?string $logger = 'kirby-mcp'): void
    {
        self::log($client, LoggingLevel::Error, $data, $logger);
    }
}
