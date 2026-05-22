<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\OAuth;

final readonly class OAuthKeySet
{
    public function __construct(
        private OAuthFileStore $store,
    ) {
    }

    public function privateKey(): string
    {
        $this->ensureKeys();
        $privateKey = file_get_contents($this->privateKeyPath());
        if (!is_string($privateKey) || trim($privateKey) === '') {
            throw new \RuntimeException('OAuth private key is not readable.');
        }

        return $privateKey;
    }

    /**
     * @return array{keys: list<array<string, string>>}
     */
    public function jwks(): array
    {
        $this->ensureKeys();
        $contents = file_get_contents($this->jwksPath());
        if (!is_string($contents)) {
            throw new \RuntimeException('OAuth JWKS is not readable.');
        }

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !isset($decoded['keys']) || !is_array($decoded['keys'])) {
            throw new \RuntimeException('OAuth JWKS is invalid.');
        }

        /** @var array{keys: list<array<string, string>>} $decoded */
        return $decoded;
    }

    public function kid(): string
    {
        $jwks = $this->jwks();
        $kid = $jwks['keys'][0]['kid'] ?? null;
        if (!is_string($kid) || $kid === '') {
            throw new \RuntimeException('OAuth JWKS key id is missing.');
        }

        return $kid;
    }

    private function ensureKeys(): void
    {
        if (is_file($this->privateKeyPath()) && is_file($this->jwksPath())) {
            return;
        }

        $dir = dirname($this->privateKeyPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($key === false) {
            throw new \RuntimeException('Failed to create OAuth RSA key.');
        }

        $privateKey = '';
        if (!openssl_pkey_export($key, $privateKey) || trim($privateKey) === '') {
            throw new \RuntimeException('Failed to export OAuth RSA private key.');
        }

        $details = openssl_pkey_get_details($key);
        if (!is_array($details) || !isset($details['rsa']) || !is_array($details['rsa'])) {
            throw new \RuntimeException('Failed to inspect OAuth RSA public key.');
        }

        $rsa = $details['rsa'];
        $n = $rsa['n'] ?? null;
        $e = $rsa['e'] ?? null;
        if (!is_string($n) || !is_string($e)) {
            throw new \RuntimeException('OAuth RSA public key is incomplete.');
        }

        $kid = substr(hash('sha256', $n . $e), 0, 24);
        $jwks = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'kid' => $kid,
                    'n' => self::base64Url($n),
                    'e' => self::base64Url($e),
                ],
            ],
        ];

        if (file_put_contents($this->privateKeyPath(), $privateKey, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write OAuth private key.');
        }
        @chmod($this->privateKeyPath(), 0600);

        if (file_put_contents($this->jwksPath(), json_encode($jwks, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write OAuth JWKS.');
        }
        @chmod($this->jwksPath(), 0644);
    }

    private function privateKeyPath(): string
    {
        return rtrim($this->store->root(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'keys'
            . DIRECTORY_SEPARATOR . 'private.pem';
    }

    private function jwksPath(): string
    {
        return rtrim($this->store->root(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'keys'
            . DIRECTORY_SEPARATOR . 'jwks.json';
    }

    public static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
