<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('preserves a forbidden status when sending a WWW-Authenticate header', function (): void {
    $autoload = realpath(__DIR__ . '/../../vendor/autoload.php');
    expect($autoload)->not()->toBeFalse();

    $script = <<<'PHP'
    require $argv[1];

    $response = new GuzzleHttp\Psr7\Response(403, [
        'WWW-Authenticate' => 'Bearer error="insufficient_scope"',
    ]);

    (new Bnomei\KirbyMcp\Mcp\KirbyMcpResponse($response))->send();
    echo http_response_code();
    PHP;

    $process = new Process([PHP_BINARY, '-r', $script, $autoload]);
    $process->mustRun();

    expect($process->getOutput())->toBe('403');
});
