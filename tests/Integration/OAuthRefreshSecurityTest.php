<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\OAuth\OAuthFileStore;
use Kirby\Filesystem\Dir;
use Symfony\Component\Process\Process;

it('serializes refresh rotation and isolates revocation to the authenticated token family', function (): void {
    $root = sys_get_temp_dir() . '/kirby-refresh-' . bin2hex(random_bytes(8));
    mkdir($root . '/.kirby-mcp', 0700, true);
    $store = new OAuthFileStore($root . '/.kirby-mcp/oauth');
    file_put_contents($root . '/.kirby-mcp/mcp.json', json_encode(['http' => [
        'enabled' => true,
        'auth' => ['mode' => 'oauth', 'issuer' => 'https://example.test', 'audience' => 'https://example.test/mcp', 'scopes' => ['kirby-mcp:read']],
        'oauthProvider' => ['enabled' => true],
    ]], JSON_THROW_ON_ERROR));
    foreach (['client-a', 'client-b'] as $client) {
        $store->write('clients', $client, ['client_id' => $client, 'token_endpoint_auth_method' => 'none', 'scope' => 'kirby-mcp:read']);
    }
    $worker = $root . '/worker.php';
    file_put_contents($worker, '<?php require ' . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ';' . <<<'PHP'
foreach (array_keys(getenv()) as $name) {
    if (str_starts_with($name, 'KIRBY_MCP_HTTP_')) {
        putenv($name);
    }
}
if (isset($argv[2])) {
    touch(__DIR__ . '/ready-' . $argv[2]);
    $deadline = microtime(true) + 10;
    while (!is_file(__DIR__ . '/start')) {
        if (microtime(true) > $deadline) throw new RuntimeException('Start barrier timed out');
        usleep(1000);
    }
}
$request = new GuzzleHttp\Psr7\ServerRequest('POST', 'https://example.test/mcp/oauth/token', ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query(json_decode($argv[1], true)));
$response = Bnomei\KirbyMcp\Mcp\KirbyMcpOAuthRoute::handle(__DIR__, $request);
echo json_encode(['status' => $response->code(), 'body' => json_decode($response->body(), true)]);
PHP);
    $run = static function (array $input) use ($worker): array {
        $process = new Process([PHP_BINARY, $worker, json_encode($input, JSON_THROW_ON_ERROR)]);
        $process->mustRun();
        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    };
    $issue = static function (string $code) use ($store, $run): array {
        $store->write('auth-codes', hash('sha256', $code), [
            'client_id' => 'client-a', 'user_id' => 'test-user', 'redirect_uri' => 'https://client.test/cb',
            'scopes' => ['kirby-mcp:read'], 'code_challenge' => str_repeat('v', 43), 'code_challenge_method' => 'plain', 'expires_at' => time() + 600,
        ]);
        $result = $run(['grant_type' => 'authorization_code', 'client_id' => 'client-a', 'code' => $code, 'redirect_uri' => 'https://client.test/cb', 'code_verifier' => str_repeat('v', 43)]);
        expect($result['status'])->toBe(200);
        return $result['body'];
    };
    $workers = [];
    try {
        $first = $issue('first');
        $unrelated = $issue('unrelated');
        $input = ['grant_type' => 'refresh_token', 'client_id' => 'client-a', 'refresh_token' => $first['refresh_token']];
        $wrongClient = $run(array_replace($input, ['client_id' => 'client-b']));
        expect($wrongClient['status'])->toBe(400);
        // Two independent PHP workers contend for the same refresh token.
        foreach (['one', 'two'] as $name) {
            $process = new Process([PHP_BINARY, $worker, json_encode($input, JSON_THROW_ON_ERROR), $name]);
            $process->start();
            $workers[] = $process;
        }
        $deadline = microtime(true) + 10;
        while (!is_file($root . '/ready-one') || !is_file($root . '/ready-two')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Workers did not reach the start barrier.');
            }
            usleep(1000);
        }
        touch($root . '/start');
        $results = [];
        foreach ($workers as $process) {
            expect($process->wait())->toBe(0, $process->getErrorOutput());
            $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }
        $statuses = array_column($results, 'status');
        sort($statuses);
        expect($statuses)->toBe([200, 400]);
        $winner = array_values(array_filter($results, fn (array $result) => $result['status'] === 200))[0];
        $revoked = $run(array_replace($input, ['refresh_token' => $winner['body']['refresh_token']]));
        expect($revoked['status'])->toBe(400);
        expect($revoked['body']['error'])->toBe('invalid_grant');
        $valid = $run(array_replace($input, ['refresh_token' => $unrelated['refresh_token']]));
        expect($valid['status'])->toBe(200);
        // Pre-upgrade tokens without family state require fresh authorization.
        $store->write('refresh-tokens', hash('sha256', 'legacy-token'), ['client_id' => 'client-a', 'user_id' => 'test-user', 'scopes' => ['kirby-mcp:read'], 'expires_at' => time() + 600]);
        expect($run(array_replace($input, ['refresh_token' => 'legacy-token']))['status'])->toBe(400);
    } finally {
        foreach ($workers as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        Dir::remove($root);
    }
});
