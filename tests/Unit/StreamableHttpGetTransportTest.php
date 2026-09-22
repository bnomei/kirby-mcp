<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('uses its configured deadline and emits initial and heartbeat SSE framing', function (): void {
    $autoload = realpath(__DIR__ . '/../../vendor/autoload.php');
    expect($autoload)->not()->toBeFalse();

    $script = <<<'PHP'
require $argv[1];

$transport = new Bnomei\KirbyMcp\Mcp\StreamableHttpGetTransport(
    sessionIdValue: Symfony\Component\Uid\Uuid::v4(),
    responseFactory: new GuzzleHttp\Psr7\HttpFactory(),
    maxSeconds: 1,
    pollIntervalMicros: 10000,
    heartbeatIntervalSeconds: 0.05,
);
$transport->listen()->getBody()->getContents();
PHP;

    $started = microtime(true);
    $process = new Process([
        PHP_BINARY,
        '-d',
        'max_execution_time=1',
        '-r',
        $script,
        $autoload,
    ], timeout: 5);
    $process->run();
    $elapsed = microtime(true) - $started;

    expect($process->getExitCode())->toBe(0)
        ->and($process->getErrorOutput())->toBe('')
        ->and($process->getOutput())->toContain(": connected\n\n")
        ->and($process->getOutput())->toContain(": keepalive\n\n")
        ->and($elapsed)->toBeGreaterThanOrEqual(0.9)
        ->and($elapsed)->toBeLessThan(3.0);
});
