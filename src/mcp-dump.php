<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\McpDump;

if (!function_exists('mcp_dump')) {
    function mcp_dump(mixed ...$values): McpDump
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return McpDump::create(
            values: $values,
            backtrace: $backtrace,
        );
    }
}
