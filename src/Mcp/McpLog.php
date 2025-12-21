<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Schema\Enum\LoggingLevel;
use Mcp\Server\RequestContext;

final class McpLog
{
    public static function log(?RequestContext $context, LoggingLevel $level, mixed $data, ?string $logger = 'kirby-mcp'): void
    {
        if ($context === null) {
            return;
        }

        if (!LoggingState::allows($level, $context->getSession())) {
            return;
        }

        $context->getClientLogger()->log($level->value, $data, $logger !== null ? ['logger' => $logger] : []);
    }

    public static function error(?RequestContext $context, mixed $data, ?string $logger = 'kirby-mcp'): void
    {
        self::log($context, LoggingLevel::Error, $data, $logger);
    }
}
