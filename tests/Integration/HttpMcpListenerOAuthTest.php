<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\HttpMcpListener;
use Bnomei\KirbyMcp\Mcp\OAuth\OAuthFileStore;
use Bnomei\KirbyMcp\Mcp\OAuth\OAuthKeySet;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Firebase\JWT\JWT;

/** @return array{0: int, 1: int} */
function listenerOAuthJwksServer(string $body): array
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
        $client = stream_socket_accept($server, 10);
        if (is_resource($client)) {
            while (($line = fgets($client)) !== false && rtrim($line, "\r\n") !== '') {
                // Consume the request headers before replying.
            }
            fwrite($client, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
            fclose($client);
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
