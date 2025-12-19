<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\SecretMasker;

it('masks OpenAI API keys', function (): void {
    $masker = new SecretMasker();

    // Old format
    $input = 'My API key is sk-' . str_repeat('a', 48);
    $result = $masker->mask($input);
    expect($result)->toBe('My API key is [REDACTED]');

    // New format with sk-proj-
    $input2 = 'Key: sk-proj-' . str_repeat('b', 48);
    $result2 = $masker->mask($input2);
    expect($result2)->toBe('Key: [REDACTED]');
});

it('masks Anthropic API keys', function (): void {
    $masker = new SecretMasker();

    // Simulated Anthropic key format
    $input = 'Key: sk-ant-api03-' . str_repeat('a', 96);
    $result = $masker->mask($input);

    expect($result)->toBe('Key: [REDACTED]');
});

it('masks GitHub PAT tokens', function (): void {
    $masker = new SecretMasker();

    $input = 'Token ghp_' . str_repeat('A', 36);
    $result = $masker->mask($input);

    expect($result)->toBe('Token [REDACTED]');
});

it('masks GitHub fine-grained PAT tokens', function (): void {
    $masker = new SecretMasker();

    $input = 'Token github_pat_' . str_repeat('A', 30);
    $result = $masker->mask($input);

    expect($result)->toBe('Token [REDACTED]');
});

it('masks GitHub OAuth and app tokens', function (): void {
    $masker = new SecretMasker();

    $oauth = 'OAuth: gho_' . str_repeat('A', 36);
    expect($masker->mask($oauth))->toBe('OAuth: [REDACTED]');

    $userToken = 'User token: ghu_' . str_repeat('B', 36);
    expect($masker->mask($userToken))->toBe('User token: [REDACTED]');

    $serverToken = 'Server token: ghs_' . str_repeat('C', 36);
    expect($masker->mask($serverToken))->toBe('Server token: [REDACTED]');
});

it('masks Stripe keys', function (): void {
    $masker = new SecretMasker();

    $input = 'Stripe: sk_test_' . str_repeat('a', 24);
    $result = $masker->mask($input);

    expect($result)->toBe('Stripe: [REDACTED]');
});

it('masks Fireworks API keys', function (): void {
    $masker = new SecretMasker();

    $input = 'Fireworks: fw_' . str_repeat('a', 24);
    $result = $masker->mask($input);

    expect($result)->toBe('Fireworks: [REDACTED]');
});

it('masks Slack app tokens', function (): void {
    $masker = new SecretMasker();

    $input = 'Slack: xapp-1-234567890-987654321-abcdef0123456789';
    $result = $masker->mask($input);

    expect($result)->toBe('Slack: [REDACTED]');
});

it('masks Google API keys', function (): void {
    $masker = new SecretMasker();

    $input = 'Google key: AIza' . str_repeat('A', 35);
    $result = $masker->mask($input);

    expect($result)->toBe('Google key: [REDACTED]');
});

it('masks Firebase auth domains', function (): void {
    $masker = new SecretMasker();

    $input = 'Firebase: my-project-123.firebaseapp.com';
    $result = $masker->mask($input);

    expect($result)->toBe('Firebase: [REDACTED]');
});

it('masks Google OAuth client IDs', function (): void {
    $masker = new SecretMasker();

    $input = 'OAuth client: 123456789012-' . str_repeat('a', 32) . '.apps.googleusercontent.com';
    $result = $masker->mask($input);

    expect($result)->toBe('OAuth client: [REDACTED]');
});

it('masks AWS access key IDs', function (): void {
    $masker = new SecretMasker();

    $input = 'AWS Key: AKIAIOSFODNN7EXAMPLE';
    $result = $masker->mask($input);

    expect($result)->toBe('AWS Key: [REDACTED]');
});

it('masks Bearer tokens', function (): void {
    $masker = new SecretMasker();

    $input = 'Authorization: Bearer abcDEF1234567890-_abc';
    $result = $masker->mask($input);

    expect($result)->toBe('Authorization: [REDACTED]');
});

it('masks JWT tokens', function (): void {
    $masker = new SecretMasker();

    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.'
        . 'eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.'
        . 'SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
    $input = 'JWT: ' . $token;
    $result = $masker->mask($input);

    expect($result)->toBe('JWT: [REDACTED]');
});

it('masks Basic auth headers', function (): void {
    $masker = new SecretMasker();

    $input = 'Authorization: Basic dXNlcjpwYXNz';
    $result = $masker->mask($input);

    expect($result)->toBe('Authorization: [REDACTED]');
});

it('masks IPv4 addresses', function (): void {
    $masker = new SecretMasker();

    $input = 'Server IP: 192.168.1.100';
    $result = $masker->mask($input);

    expect($result)->toBe('Server IP: [REDACTED]');
});

it('masks MAC addresses', function (): void {
    $masker = new SecretMasker();

    $input = 'Device MAC: AA:BB:CC:DD:EE:FF';
    $result = $masker->mask($input);

    expect($result)->toBe('Device MAC: [REDACTED]');
});

it('masks password fields in config strings', function (): void {
    $masker = new SecretMasker();

    $input = '"password": "mysecretpassword123"';
    $result = $masker->mask($input);

    expect($result)->toBe('[REDACTED]');
});

it('recursively masks secrets in arrays', function (): void {
    $masker = new SecretMasker();

    $input = [
        'config' => [
            'api_key' => 'sk-' . str_repeat('a', 48),
            'server' => '192.168.1.1',
            'safe' => 'this is fine',
        ],
        'nested' => [
            'deep' => [
                'token' => 'ghp_' . str_repeat('X', 36),
            ],
        ],
    ];

    $result = $masker->maskRecursive($input);

    expect($result['config']['api_key'])->toBe('[REDACTED]');
    expect($result['config']['server'])->toBe('[REDACTED]');
    expect($result['config']['safe'])->toBe('this is fine');
    expect($result['nested']['deep']['token'])->toBe('[REDACTED]');
});

it('can be disabled with empty patterns array', function (): void {
    $masker = new SecretMasker([]);

    $input = 'sk-' . str_repeat('a', 48);
    $result = $masker->mask($input);

    expect($result)->toBe($input);
    expect($masker->isEnabled())->toBeFalse();
});

it('supports custom patterns', function (): void {
    $masker = new SecretMasker([
        '/\bCUSTOM_[A-Z_]+\b/',  // Include underscores in character class
    ]);

    $input = 'Token: CUSTOM_SECRET_TOKEN';
    $result = $masker->mask($input);

    expect($result)->toBe('Token: [REDACTED]');

    // Default patterns should not apply
    $defaultInput = 'sk-' . str_repeat('a', 48);
    expect($masker->mask($defaultInput))->toBe($defaultInput);
});

it('supports custom mask string', function (): void {
    $masker = new SecretMasker(null, '***');

    $input = '192.168.1.1';
    $result = $masker->mask($input);

    expect($result)->toBe('***');
});

it('handles invalid regex patterns gracefully', function (): void {
    // Suppress the warning that preg_replace emits for invalid patterns
    $previousLevel = error_reporting(E_ALL & ~E_WARNING);

    try {
        $masker = new SecretMasker([
            '/[invalid(regex/',  // Invalid pattern
            '/\btest\b/',        // Valid pattern
        ]);

        $input = 'This is a test value';
        $result = $masker->mask($input);

        expect($result)->toBe('This is a [REDACTED] value');
    } finally {
        error_reporting($previousLevel);
    }
});

it('preserves non-string values in recursive masking', function (): void {
    $masker = new SecretMasker();

    $input = [
        'count' => 42,
        'enabled' => true,
        'ratio' => 3.14,
        'nothing' => null,
        'text' => '192.168.1.1',
    ];

    $result = $masker->maskRecursive($input);

    expect($result['count'])->toBe(42);
    expect($result['enabled'])->toBe(true);
    expect($result['ratio'])->toBe(3.14);
    expect($result['nothing'])->toBeNull();
    expect($result['text'])->toBe('[REDACTED]');
});

it('provides list of default pattern names', function (): void {
    $names = SecretMasker::defaultPatternNames();

    expect($names)->toContain('OpenAI API Key');
    expect($names)->toContain('Anthropic API Key');
    expect($names)->toContain('GitHub Classic PAT');
    expect($names)->toContain('AWS Access Key ID');
    expect($names)->toContain('Stripe Key');
});
