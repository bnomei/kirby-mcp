<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

function runMcpLogDiagnostic(string $php): Process
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    $process = new Process([PHP_BINARY, '-r', "require " . var_export($autoload, true) . ";\n" . $php]);
    $process->mustRun();

    return $process;
}

function mcpLogContextPhp(array $traceContext): string
{
    return sprintf(<<<'PHP'
        $session = new \Mcp\Server\Session\Session(new \Mcp\Server\Session\InMemorySessionStore(60));
        $session->set(\Mcp\Server\Stateless\RequestMeta::class, new \Mcp\Server\Stateless\RequestMeta(
            '2026-07-28',
            \Mcp\Schema\ClientCapabilities::fromArray([]),
            traceContext: %s,
        ));
        $context = new \Mcp\Server\RequestContext($session, new \Mcp\Schema\Request\CallToolRequest('kirby_init', []));
        \Bnomei\KirbyMcp\Mcp\McpLog::error($context, ['message' => 'failed']);
        PHP, var_export($traceContext, true));
}

it('emits a single JSON diagnostic even with a null context', function (): void {
    $process = runMcpLogDiagnostic(<<<'PHP'
        \Bnomei\KirbyMcp\Mcp\McpLog::error(null, ['message' => 'Hällo / Kirby'], 'test/logger');
        PHP);

    expect($process->getOutput())->toBe('');

    $lines = preg_split('/\R/', trim($process->getErrorOutput()));
    expect($lines)->toHaveCount(1);

    $payload = json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR);
    expect($payload)->toBe([
        'level' => 'error',
        'logger' => 'test/logger',
        'data' => ['message' => 'Hällo / Kirby'],
    ]);
});

it('substitutes invalid UTF-8 and handles difficult payloads without throwing', function (): void {
    $process = runMcpLogDiagnostic(<<<'PHP'
        $recursive = ['invalid' => "\xB1\x31"];
        $recursive['self'] = &$recursive;
        \Bnomei\KirbyMcp\Mcp\McpLog::error(null, $recursive);
        PHP);

    expect($process->getOutput())->toBe('');
    $payload = json_decode(trim($process->getErrorOutput()), true, flags: JSON_THROW_ON_ERROR);
    expect($payload['level'])->toBe('error')
        ->and($payload['logger'])->toBe('kirby-mcp')
        ->and($payload['data']['invalid'])->toContain('�')
        ->and($payload['data'])->toHaveKey('self');
});

it('does not serialize object details or exception traces', function (): void {
    $process = runMcpLogDiagnostic(<<<'PHP'
        $data = new class implements \JsonSerializable {
            public string $secret = 'must-not-leak';
            public function jsonSerialize(): mixed { throw new \RuntimeException('also-secret'); }
        };
        \Bnomei\KirbyMcp\Mcp\McpLog::error(null, $data, 'custom');
        PHP);

    expect($process->getOutput())->toBe('');
    $diagnostic = trim($process->getErrorOutput());
    $payload = json_decode($diagnostic, true, flags: JSON_THROW_ON_ERROR);
    expect($payload['level'])->toBe('error')
        ->and($payload['logger'])->toBe('custom')
        ->and($payload['data'])->toHaveKey('type')
        ->and($diagnostic)->not()->toContain('must-not-leak')
        ->and($diagnostic)->not()->toContain('also-secret');
});

it('includes a valid W3C v00 traceparent', function (): void {
    $traceparent = '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01';
    $process = runMcpLogDiagnostic(mcpLogContextPhp(['traceparent' => $traceparent]));
    $payload = json_decode(trim($process->getErrorOutput()), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['traceparent'])->toBe($traceparent);
});

it('omits malformed and zero-ID traceparents', function (string $traceparent): void {
    $process = runMcpLogDiagnostic(mcpLogContextPhp(['traceparent' => $traceparent]));
    $payload = json_decode(trim($process->getErrorOutput()), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->not()->toHaveKey('traceparent');
})->with([
    'uppercase hex' => '00-4BF92F3577B34DA6A3CE929D0E0E4736-00f067aa0ba902b7-01',
    'unsupported version' => '01-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01',
    'zero trace ID' => '00-00000000000000000000000000000000-00f067aa0ba902b7-01',
    'zero parent ID' => '00-4bf92f3577b34da6a3ce929d0e0e4736-0000000000000000-01',
    'oversized' => '00-4bf92f3577b34da6a3ce929d0e0e47360-00f067aa0ba902b7-01',
]);

it('does not leak tracestate or baggage', function (): void {
    $process = runMcpLogDiagnostic(mcpLogContextPhp([
        'traceparent' => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01',
        'tracestate' => 'vendor=tenant-secret',
        'baggage' => 'userId=sensitive-user',
    ]));
    $diagnostic = trim($process->getErrorOutput());
    $payload = json_decode($diagnostic, true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->not()->toHaveKeys(['tracestate', 'baggage'])
        ->and($diagnostic)->not()->toContain('tenant-secret')
        ->and($diagnostic)->not()->toContain('sensitive-user');
});
