<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\OAuth\OAuthFileStore;

function oauthFileStoreTempRoot(): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-oauth-store-' . bin2hex(random_bytes(8));
}

it('take() returns a stored record exactly once (single-use)', function (): void {
    $root = oauthFileStoreTempRoot();
    $store = new OAuthFileStore($root);

    try {
        $store->write('auth-codes', 'code-1', ['user_id' => 'u1', 'scopes' => ['kirby-mcp:read']]);

        $first = $store->take('auth-codes', 'code-1');
        $second = $store->take('auth-codes', 'code-1');

        expect($first)->toBe(['user_id' => 'u1', 'scopes' => ['kirby-mcp:read']]);
        expect($second)->toBeNull();
        expect($store->read('auth-codes', 'code-1'))->toBeNull();
    } finally {
        if (is_dir($root)) {
            exec('rm -rf ' . escapeshellarg($root));
        }
    }
});

it('take() returns null for a missing record', function (): void {
    $root = oauthFileStoreTempRoot();
    $store = new OAuthFileStore($root);

    try {
        expect($store->take('auth-codes', 'does-not-exist'))->toBeNull();
    } finally {
        if (is_dir($root)) {
            exec('rm -rf ' . escapeshellarg($root));
        }
    }
});
