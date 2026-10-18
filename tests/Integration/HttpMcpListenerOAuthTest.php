<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\HttpMcpListener;
use Bnomei\KirbyMcp\Mcp\OAuth\OAuthFileStore;
use Bnomei\KirbyMcp\Mcp\OAuth\OAuthKeySet;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Firebase\JWT\JWT;

/** @return array{0: int, 1: int} */
function listenerOAuthJwksServer(string|Closure $body): array
{
    $errno = 0;
    $error = '';
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($server === false) {
        throw new RuntimeException('Could not start test JWKS server: ' . $error);
    }
    $address = stream_socket_get_name($server, false);
    $port = (int) substr((string) $address, strrpos((string) $address, ':') + 1);
    $pid = pcntl_fork();
    if ($pid === 0) {
        if ($body instanceof Closure) {
            $body = $body($port);
        }
        while (true) {
            $client = stream_socket_accept($server, 10);
            if ($client === false) {
                break;
            }
            if (is_resource($client)) {
                $request = fgets($client);
                while (($line = fgets($client)) !== false && rtrim($line, "\r\n") !== '') {
                    // Consume the request headers before replying.
                }
                $response = is_array($body) ? $body[explode(' ', (string) $request)[1]] : $body;
                fwrite($client, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: " . strlen($response) . "\r\nConnection: close\r\n\r\n" . $response);
                fclose($client);
            }
        }
        fclose($server);
        exit(0);
    }
    fclose($server);
    if ($pid < 0) {
        throw new RuntimeException('Could not fork test JWKS server.');
    }

    return [$pid, $port];
}

function listenerOAuthFreePort(): int
{
    $server = stream_socket_server('tcp://127.0.0.1:0');
    if ($server === false) {
        throw new RuntimeException('Could not reserve a listener test port.');
    }
    $address = stream_socket_get_name($server, false);
    fclose($server);

    return (int) substr((string) $address, strrpos((string) $address, ':') + 1);
}

function listenerOAuthRequest(int $port, string $token): string
{
    $client = false;
    set_error_handler(static fn (): bool => true);
    try {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $client = stream_socket_client('tcp://127.0.0.1:' . $port, timeout: 0.1);
            if ($client !== false) {
                break;
            }
            usleep(20_000);
        }
    } finally {
        restore_error_handler();
    }
    if (!is_resource($client)) {
        throw new RuntimeException('HTTP MCP listener did not become ready.');
    }

    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-11-05',
            'capabilities' => new stdClass(),
            'clientInfo' => ['name' => 'tests', 'version' => 'dev'],
        ],
    ], JSON_THROW_ON_ERROR);
    fwrite($client, "POST /mcp HTTP/1.1\r\nHost: 127.0.0.1:{$port}\r\nAuthorization: Bearer {$token}\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
    stream_set_timeout($client, 10);
    $response = stream_get_contents($client);
    fclose($client);

    return is_string($response) ? $response : '';
}

function listenerOAuthStop(int $pid): void
{
    if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
        posix_kill($pid, SIGTERM);
        pcntl_waitpid($pid, $status);
    }
}

function listenerOAuthRemoveTree(string $root): void
{
    if (!is_dir($root)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($root);
}

it('keeps OAuth subject binding for an empty shared-token override while static overrides stay trusted', function (): void {
    if (!function_exists('pcntl_fork') || !function_exists('posix_kill')) {
        $this->markTestSkipped('pcntl and posix are required for listener subprocess tests.');
    }

    $root = sys_get_temp_dir() . '/kirby-mcp-listener-oauth-' . bin2hex(random_bytes(6));
    mkdir($root . '/.kirby-mcp', 0777, true);
    $keys = new OAuthKeySet(new OAuthFileStore($root . '/oauth-fixture'));
    [$jwksPid, $jwksPort] = listenerOAuthJwksServer(json_encode($keys->jwks(), JSON_THROW_ON_ERROR));
    $issuer = 'https://issuer.example.test';
    $audience = 'https://mcp.example.test/mcp';
    file_put_contents($root . '/.kirby-mcp/mcp.json', json_encode([
        'http' => ['auth' => [
            'mode' => 'oauth',
            'issuer' => $issuer,
            'audience' => $audience,
            'jwksUri' => 'http://127.0.0.1:' . $jwksPort . '/jwks.json',
        ]],
    ], JSON_THROW_ON_ERROR));
    KirbyMcpConfig::clearCache();
    $tokenWithoutSubject = JWT::encode([
        'iss' => $issuer,
        'aud' => $audience,
        'iat' => time() - 1,
        'exp' => time() + 60,
        'scope' => HttpAuthScopes::READ,
    ], $keys->privateKey(), 'RS256', $keys->kid());
    $listenerPids = [];

    try {
        $oauthPort = listenerOAuthFreePort();
        $listenerPids[] = $oauthPid = pcntl_fork();
        if ($oauthPid === 0) {
            exit((new HttpMcpListener())->serve('127.0.0.1', $oauthPort, '/mcp', $root, sharedToken: '', once: true));
        }
        if ($oauthPid < 0) {
            throw new RuntimeException('Could not fork OAuth listener.');
        }
        $oauthResponse = listenerOAuthRequest($oauthPort, $tokenWithoutSubject);
        pcntl_waitpid($oauthPid, $status);
        expect($oauthResponse)->toStartWith('HTTP/1.1 401')
            ->and($oauthResponse)->toContain('invalid_token');

        $staticPort = listenerOAuthFreePort();
        $listenerPids[] = $staticPid = pcntl_fork();
        if ($staticPid === 0) {
            exit((new HttpMcpListener())->serve('127.0.0.1', $staticPort, '/mcp', $root, sharedToken: 'local-secret', once: true));
        }
        if ($staticPid < 0) {
            throw new RuntimeException('Could not fork static-token listener.');
        }
        $staticResponse = listenerOAuthRequest($staticPort, 'local-secret');
        pcntl_waitpid($staticPid, $status);
        expect($staticResponse)->toStartWith('HTTP/1.1 200');
    } finally {
        foreach ($listenerPids as $pid) {
            if ($pid > 0) {
                listenerOAuthStop($pid);
            }
        }
        listenerOAuthStop($jwksPid);
        listenerOAuthRemoveTree($root);
        KirbyMcpConfig::clearCache();
    }
});

it('authenticates external RSA tokens through the configured Kirby route and rejects invalid claims', function (bool $discovery, string $algorithm): void {
    if (!function_exists('pcntl_fork') || !function_exists('posix_kill')) {
        $this->markTestSkipped('pcntl and posix are required for local OAuth fixtures.');
    }
    $root = sys_get_temp_dir() . '/kirby-mcp-external-oauth-' . bin2hex(random_bytes(6));
    mkdir($root . '/.kirby-mcp', 0777, true);
    symlink(dirname(__DIR__, 2) . '/vendor', $root . '/vendor');
    copy(cmsPath() . '/composer.json', $root . '/composer.json');
    file_put_contents($root . '/index.php', '<?php require __DIR__ . "/vendor/autoload.php"; echo (new Kirby(["roots" => ["index" => __DIR__]]))->render();');
    (new Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller())->install(
        $root,
        commandsRootOverride: $root . '/site/commands',
        pluginsRootOverride: $root . '/site/plugins',
    );
    $keys = new OAuthKeySet(new OAuthFileStore($root . '/keys'));
    $otherKeys = new OAuthKeySet(new OAuthFileStore($root . '/other-keys'));
    $jwks = $keys->jwks();
    $jwks['keys'][0]['alg'] = $algorithm;
    [$pid, $port] = listenerOAuthJwksServer(static function (int $port) use ($jwks): array {
        $issuer = 'http://127.0.0.1:' . $port;

        return [
            '/.well-known/oauth-authorization-server' => json_encode(['issuer' => $issuer, 'jwks_uri' => $issuer . '/jwks'], JSON_THROW_ON_ERROR),
            '/jwks' => json_encode($jwks, JSON_THROW_ON_ERROR),
        ];
    });
    $issuer = 'http://127.0.0.1:' . $port;
    $audience = 'https://mcp.example.test/mcp';
    $errorHandlers = captureErrorHandlers();
    $whoops = Kirby\Cms\App::$enableWhoops;
    Kirby\Cms\App::$enableWhoops = false;
    $previous = Kirby\Cms\App::instance(null, true);
    $previous?->session()->commit();
    $plugins = $previous?->plugins() ?? [];
    $previous?->plugins([]);
    $app = new Kirby\Cms\App(['roots' => ['index' => $root], 'options' => ['debug' => false]]);
    try {
        $user = ensureUser($app, 'external-oauth@example.test');
        $auth = ['mode' => 'oauth', 'issuer' => $issuer, 'audience' => $audience];
        if (!$discovery) {
            $auth['jwksUri'] = $issuer . '/jwks';
        }
        file_put_contents($root . '/.kirby-mcp/mcp.json', json_encode(['http' => ['enabled' => true, 'auth' => $auth]], JSON_THROW_ON_ERROR));
        KirbyMcpConfig::clearCache();
        $claims = ['iss' => $issuer, 'aud' => $audience, 'sub' => $user->id(), 'exp' => time() + 300, 'scope' => HttpAuthScopes::READ];
        $factory = new GuzzleHttp\Psr7\HttpFactory();
        $request = $factory->createServerRequest('POST', $audience)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json, text/event-stream')
            ->withHeader('Mcp-Method', 'tools/list')
            ->withBody($factory->createStream(json_encode([
                'jsonrpc' => '2.0', 'id' => 42, 'method' => 'tools/list',
                'params' => ['_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                    'io.modelcontextprotocol/clientCapabilities' => new stdClass(),
                    'io.modelcontextprotocol/clientInfo' => ['name' => 'tests', 'version' => 'dev'],
                ]],
            ], JSON_THROW_ON_ERROR)))
            ->withHeader('MCP-Protocol-Version', '2026-07-28');
        $cases = [
            'valid' => [$claims, $keys->privateKey(), $algorithm, 200, null],
            'signature' => [$claims, $otherKeys->privateKey(), $algorithm, 401, 'Token validation failed.'],
            'issuer' => [array_replace($claims, ['iss' => $issuer . '/wrong']), $keys->privateKey(), $algorithm, 401, 'Token issuer mismatch.'],
            'audience' => [array_replace($claims, ['aud' => $audience . '/wrong']), $keys->privateKey(), $algorithm, 401, 'Token audience mismatch.'],
            'expiry' => [array_replace($claims, ['exp' => time() - 60]), $keys->privateKey(), $algorithm, 401, 'Token has expired.'],
            'algorithm' => [$claims, str_repeat('hmac-secret', 4), 'HS256', 401, 'Token algorithm is not accepted.'],
        ];
        foreach ($cases as $case => [$payload, $key, $alg, $status, $error]) {
            $token = JWT::encode($payload, $key, $alg, $keys->kid());
            $response = Bnomei\KirbyMcp\Mcp\KirbyMcpRoute::handle($root, $request->withHeader('Authorization', 'Bearer ' . $token));
            if ($response->code() !== $status) {
                throw new RuntimeException($case . ': ' . $response->body());
            }
            expect($response->code())->toBe($status);
            if ($status === 200) {
                expect($response->body())->toContain('"tools"', 'kirby_');
            } else {
                expect($response->headers()['WWW-Authenticate'] ?? '')->toContain('invalid_token', $error);
            }
        }
    } finally {
        $app->session()->commit();
        $app->plugins($plugins);
        Kirby\Cms\App::instance($previous);
        Kirby\Cms\App::$enableWhoops = $whoops;
        restoreErrorHandlers($errorHandlers);
        listenerOAuthStop($pid);
        unlink($root . '/vendor');
        listenerOAuthRemoveTree($root);
        KirbyMcpConfig::clearCache();
    }
})->with([true, false])->with(['RS256', 'RS384', 'RS512']);
