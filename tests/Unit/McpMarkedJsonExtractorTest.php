<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\Support\JsonMarkers;

function markedStdout(string $jsonBody): string
{
    // Mirrors RuntimeCommand::emit(): each marker on its own line.
    return JsonMarkers::START . "\n" . $jsonBody . "\n" . JsonMarkers::END . "\n";
}

it('extracts the framed JSON payload', function (): void {
    $stdout = markedStdout('{"ok":true,"value":123}');

    expect(McpMarkedJsonExtractor::extract($stdout))->toBe(['ok' => true, 'value' => 123]);
});

it('extracts content that contains the END marker as a substring', function (): void {
    $payload = ['ok' => true, 'content' => ['text' => 'before __KIRBY_MCP_JSON_END__ after']];
    $stdout = markedStdout(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    $result = McpMarkedJsonExtractor::extract($stdout);

    expect($result)->toBe($payload);
    expect($result['content']['text'])->toContain('__KIRBY_MCP_JSON_END__');
});

it('extracts content containing the END marker even with single-line framing', function (): void {
    // Some emitters frame markers inline without their own lines.
    $body = json_encode(['ok' => true, 'text' => 'x __KIRBY_MCP_JSON_END__ y'], JSON_UNESCAPED_SLASHES);
    $stdout = JsonMarkers::START . $body . JsonMarkers::END;

    expect(McpMarkedJsonExtractor::extract($stdout))
        ->toBe(['ok' => true, 'text' => 'x __KIRBY_MCP_JSON_END__ y']);
});

it('returns null when no markers are present', function (): void {
    expect(McpMarkedJsonExtractor::extract('plain output, no markers'))->toBeNull();
});
